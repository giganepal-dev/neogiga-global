<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Account\CustomerIdentityService;
use App\Services\Bom\BomComponentMatcher;
use App\Services\Bom\BomImportParser;
use App\Services\Bom\BomImportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BomPageController extends Controller
{
    public function __construct(
        private readonly BomImportService $imports,
        private readonly CustomerIdentityService $identity,
    ) {}

    public function index(Request $request): View
    {
        return view('frontend.bom.index', [
            'customer' => $this->identity->defaults($request->user()),
        ]);
    }

    public function match(Request $request): View
    {
        $input = trim((string) $request->input('bom', ''));
        $format = (string) $request->input('format', 'paste');

        $lines = [];
        $error = null;

        // File intake: CSV/TXT read as-is; XLSX/XLS converted to CSV text.
        // Everything funnels into the same parser path as pasted input.
        if ($request->hasFile('bom_file')) {
            $file = $request->file('bom_file');
            $ext = strtolower((string) $file->getClientOriginalExtension());

            if (! $file->isValid() || $file->getSize() > 5 * 1024 * 1024) {
                $error = 'Upload failed or file exceeds 5 MB.';
            } elseif (! in_array($ext, ['csv', 'txt', 'tsv', 'xlsx', 'xls'], true)) {
                $error = 'Unsupported file type. Upload a CSV, TXT, or Excel (.xlsx / .xls) file.';
            } else {
                try {
                    if (in_array($ext, ['xlsx', 'xls'], true)) {
                        $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
                        $rows = [];
                        foreach ($sheet->toArray(null, true, false) as $row) {
                            // ponytail: commas inside cells become spaces so the
                            // CSV line stays column-stable for the parser.
                            $cells = array_map(fn ($c) => str_replace(["\r", "\n", ','], ' ', trim((string) $c)), $row);
                            if (implode('', $cells) !== '') {
                                $rows[] = implode(',', $cells);
                            }
                        }
                        $input = implode("\n", $rows);
                    } else {
                        $input = trim((string) file_get_contents($file->getRealPath()));
                    }
                    $format = 'paste';
                } catch (\Throwable $e) {
                    $error = 'Could not read the uploaded file: '.$e->getMessage();
                }
            }
        }

        if ($error !== null) {
            // fall through to the results section with the error set
        } elseif ($input === '') {
            $error = 'Upload a BOM file or paste your parts list above.';
        } else {
            try {
                $parser = app(BomImportParser::class);
                $parsed = $parser->parse($input, $format);
                // parse() returns a wrapper: ['lines' => [...], 'delimiter', 'mapped', 'has_header']
                $lines = $parsed['lines'] ?? [];
            } catch (\Throwable $e) {
                $error = 'Could not parse input: '.$e->getMessage();
            }
        }

        // Merge parser line data with matcher output into view-ready rows.
        // (The matcher returns matched_product_id/match_status keyed by line_no;
        // rendering the raw matcher output showed every line as unmatched.)
        $results = [];
        if ($lines && ! $error) {
            $matchResults = app(BomComponentMatcher::class)->match($lines);
            foreach ($lines as $index => $line) {
                $key = $line['line_no'] ?? $index;
                $m = $matchResults[$key] ?? [];
                $results[] = [
                    'mpn' => $line['mpn'] ?? null,
                    'manufacturer' => $line['manufacturer'] ?? null,
                    'quantity' => (int) ($line['quantity'] ?? 1),
                    'product_id' => $m['matched_product_id'] ?? null,
                    'status' => $m['match_status'] ?? 'none',
                    'confidence' => $m['match_confidence'] ?? 0,
                    'candidates' => $m['candidates'] ?? [],
                    'suggestions' => $m['suggestions'] ?? [],
                ];
            }
        }

        $totalLines = count($lines);
        $matched = count(array_filter($results, fn ($r) => $r['product_id'] !== null));
        $partial = count(array_filter($results, fn ($r) => $r['product_id'] === null && ($r['candidates'] !== [] || $r['suggestions'] !== [])));
        $savedImport = null;

        if ($request->user() && $lines && ! $error) {
            $uploaded = $request->file('bom_file');
            $name = $uploaded
                ? pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME)
                : 'BOM '.now()->format('Y-m-d H:i');
            $sourceFormat = $uploaded
                ? strtolower((string) $uploaded->getClientOriginalExtension())
                : 'paste';

            $savedImport = $this->imports->createFromContent(
                $request->user()->id,
                mb_substr($name, 0, 160),
                $input,
                $sourceFormat,
            );
        }

        return view('frontend.bom.index', [
            'input' => $input,
            'format' => $format,
            'error' => $error,
            'results' => $results,
            'totalLines' => $totalLines,
            'matched' => $matched,
            'partial' => $partial,
            'unmatched' => $totalLines - $matched,
            'savedImport' => $savedImport,
            'customer' => $this->identity->defaults($request->user()),
        ]);
    }
}
