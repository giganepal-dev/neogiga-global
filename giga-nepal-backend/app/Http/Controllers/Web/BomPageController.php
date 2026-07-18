<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Bom\BomComponentMatcher;
use App\Services\Bom\BomImportParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BomPageController extends Controller
{
    public function index(): View
    {
        return view('frontend.bom.index');
    }

    public function match(Request $request): View
    {
        $input = trim((string) $request->input('bom', ''));
        $format = (string) $request->input('format', 'paste');

        $lines = [];
        $error = null;

        if ($input === '') {
            $error = 'Paste your BOM or parts list above.';
        } else {
            try {
                $parser = app(BomImportParser::class);
                $lines = $parser->parse($input, $format);
            } catch (\Throwable $e) {
                $error = 'Could not parse input: ' . $e->getMessage();
            }
        }

        $results = [];
        if ($lines && ! $error) {
            $matcher = app(BomComponentMatcher::class);
            $results = $matcher->match($lines);
        }

        $totalLines = count($lines);
        $matched = count(array_filter($results, fn ($r) => ($r['product_id'] ?? null) !== null));
        $partial = count(array_filter($results, fn ($r) => ($r['confidence'] ?? '') === 'partial'));

        return view('frontend.bom.index', [
            'input' => $input,
            'format' => $format,
            'error' => $error,
            'results' => $results,
            'totalLines' => $totalLines,
            'matched' => $matched,
            'partial' => $partial,
            'unmatched' => $totalLines - $matched,
        ]);
    }
}
