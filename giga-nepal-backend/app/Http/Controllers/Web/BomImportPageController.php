<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Bom\BomImport;
use App\Services\Bom\BomImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class BomImportPageController extends Controller
{
    public function index(Request $request): View
    {
        $imports = collect();
        $selectedImport = null;

        if ($request->user()) {
            $imports = BomImport::query()
                ->where('user_id', $request->user()->id)
                ->withCount('lines')
                ->latest('id')
                ->limit(12)
                ->get();

            if ($request->filled('import')) {
                $selectedImport = BomImport::query()
                    ->where('user_id', $request->user()->id)
                    ->whereKey((int) $request->query('import'))
                    ->with('lines.matchedProduct:id,name,slug,sku,mpn')
                    ->first();
            }
        }

        return view('frontend.bom-imports.index', [
            'imports' => $imports,
            'selectedImport' => $selectedImport,
            'localePrefix' => $this->localePrefix($request),
        ]);
    }

    public function store(Request $request, BomImportService $imports): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'content' => ['required_without:file', 'nullable', 'string', 'max:1000000'],
            'file' => ['required_without:content', 'nullable', 'file', 'mimes:csv,txt,tsv', 'max:5120'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $sourceFile = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $content = (string) file_get_contents($file->getRealPath());
            $format = strtolower($file->getClientOriginalExtension() ?: 'csv');
            $sourceFile = $file->getClientOriginalName();
        } else {
            $content = (string) ($data['content'] ?? '');
            $format = 'paste';
        }

        try {
            $import = $imports->createFromContent(
                $request->user()->id,
                $data['name'],
                $content,
                $format,
                strtoupper($data['currency'] ?? 'USD'),
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['content' => $exception->getMessage()]);
        }

        $metadata = $import->meta ?? [];
        $metadata['source'] = [
            'source_name' => 'NeoGiga customer BOM uploader',
            'source_url' => $request->fullUrl(),
            'source_file' => $sourceFile,
            'source_page_url' => $request->url(),
            'downloaded_at' => null,
            'imported_at' => now()->toIso8601String(),
            'data_year' => now()->year,
            'license_note' => 'Customer-supplied procurement BOM for matching and quotation.',
            'confidence_level' => 'source_supplied',
            'original_raw_value' => $sourceFile ?: 'pasted BOM content',
            'normalized_value' => 'bom_procurement_import',
        ];
        $import->update(['meta' => $metadata]);

        return redirect()->route('localized.bom-imports', [
            'localePrefix' => $this->localePrefix($request),
            'import' => $import->id,
        ])->with('status', "BOM {$import->name} was parsed and matched against the NeoGiga catalog.");
    }

    private function localePrefix(Request $request): string
    {
        $prefix = strtolower((string) $request->route('localePrefix'));

        return array_key_exists($prefix, config('neogiga_global.prefixes', []))
            ? $prefix
            : config('neogiga_global.default_prefix', 'en');
    }
}
