<?php

/**
 * Match products without images to LCSC part numbers via local SQLite DB,
 * then download images from LCSC CDN.
 *
 * Usage: php storage/reports/match_and_download_images.php [--limit=N]
 *
 * # ponytail: offline batch job, single-threaded; queue jobs if throughput matters
 */

$sqlitePath = __DIR__ . '/../../../../jlcpcb-components.sqlite3';
if (! file_exists($sqlitePath)) {
    echo "ERROR: SQLite DB not found at {$sqlitePath}\n";
    exit(1);
}

$db = new SQLite3($sqlitePath);

// Connect to production Postgres via Laravel
require __DIR__ . '/../../bootstrap/app.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Marketplace\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

$limit = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = (int) explode('=', $arg)[1];
    }
}

// Get products without images
$query = DB::table('products')
    ->leftJoin('product_images', function ($join) {
        $join->on('products.id', '=', 'product_images.product_id')
            ->where('product_images.is_active', true);
    })
    ->whereNull('product_images.id')
    ->whereNotNull('products.mpn')
    ->where('products.mpn', '!=', '')
    ->select('products.id', 'products.mpn', 'products.manufacturer_name');

if ($limit) {
    $query->limit($limit);
}

$products = $query->get();

echo "Found {$products->count()} products without images\n";

$found = 0;
$downloaded = 0;

foreach ($products as $product) {
    $mpn = trim($product->mpn);
    $mfr = trim((string) ($product->manufacturer_name ?? ''));

    // Search SQLite for matching component
    // Strategy: try exact MPN match first, then manufacturer + partial MPN
    $lcsc = null;

    // Exact MPN match (case-insensitive)
    $stmt = $db->prepare('SELECT lcsc FROM components WHERE UPPER(mfr) = UPPER(?) LIMIT 1');
    $stmt->bindValue(1, $mpn, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        $lcsc = $row['lcsc'];
    }

    // Try manufacturer search: find manufacturer, then look for similar MPN
    if (! $lcsc && $mfr !== '') {
        // Get manufacturer ID
        $stmt = $db->prepare("SELECT id FROM manufacturers WHERE name LIKE ? LIMIT 1");
        $stmt->bindValue(1, "%{$mfr}%", SQLITE3_TEXT);
        $result = $stmt->execute();
        $mfrRow = $result->fetchArray(SQLITE3_ASSOC);

        if ($mfrRow) {
            // Search components by manufacturer with MPN substring
            $mpnBase = str_replace(['-', '_', ' '], '%', $mpn);
            $stmt = $db->prepare(
                'SELECT lcsc FROM components WHERE manufacturer_id = ? AND (mfr LIKE ? OR description LIKE ?) LIMIT 1',
            );
            $stmt->bindValue(1, $mfrRow['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, "%{$mpnBase}%", SQLITE3_TEXT);
            $stmt->bindValue(3, "%{$mpnBase}%", SQLITE3_TEXT);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row) {
                $lcsc = $row['lcsc'];
            }
        }
    }

    // Broad search by MPN substring
    if (! $lcsc) {
        $stmt = $db->prepare('SELECT lcsc FROM components WHERE mfr LIKE ? OR description LIKE ? LIMIT 1');
        $searchTerm = "%{$mpn}%";
        $stmt->bindValue(1, $searchTerm, SQLITE3_TEXT);
        $stmt->bindValue(2, $searchTerm, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row) {
            $lcsc = $row['lcsc'];
        }
    }

    if (! $lcsc) {
        continue;
    }

    $found++;
    $imageUrl = "https://assets.lcsc.com/images/lcsc/900x900/{$lcsc}.jpg";

    // Download and verify image
    $response = Http::withHeaders([
        'User-Agent' => 'Mozilla/5.0 (compatible; NeoGigaBot/1.0)',
    ])->timeout(15)->get($imageUrl);

    if (! $response->successful() || strlen($response->body()) < 1024) {
        // Try 300x300 fallback
        $imageUrl = "https://assets.lcsc.com/images/lcsc/300x300/{$lcsc}.jpg";
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; NeoGigaBot/1.0)',
        ])->timeout(15)->get($imageUrl);

        if (! $response->successful() || strlen($response->body()) < 1024) {
            continue;
        }
    }

    $ext = 'jpg';
    $filename = 'products/lcsc/' . $product->id . '_' . $lcsc . '.' . $ext;

    Storage::disk('public')->put($filename, $response->body());

    ProductImage::create([
        'product_id' => $product->id,
        'file_path' => $filename,
        'original_url' => $imageUrl,
        'source_url' => "https://www.lcsc.com/product-detail/_C{$lcsc}.html",
        'is_active' => true,
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    $downloaded++;
    echo "  #{$product->id}: {$mpn} → LCSC #{$lcsc} ✓\n";

    // Rate limit
    usleep(500000); // 0.5s between downloads
}

echo "\nDone: matched {$found}, downloaded {$downloaded} out of {$products->count()}\n";
