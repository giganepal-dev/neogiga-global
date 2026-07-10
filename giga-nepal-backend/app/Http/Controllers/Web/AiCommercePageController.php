<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Bom\BomProject;
use App\Models\Marketplace\Product;
use App\Services\CommerceAi\CommerceAiService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\RegionalCommerceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AiCommercePageController extends Controller
{
    public function index(Request $request, CommerceAiService $ai): Response
    {
        $prompt = (string) $request->query('prompt', $request->query('part', ''));

        return $this->view($request, $ai, $prompt ?: null, null);
    }

    public function build(Request $request, CommerceAiService $ai): Response|RedirectResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'min:4', 'max:1000'],
        ]);

        return $this->view($request, $ai, $data['prompt'], $this->regionalize($request, $ai->buildBom($data['prompt'], null, $request->user()?->id)));
    }

    public function save(Request $request, CommerceAiService $ai): RedirectResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'min:4', 'max:1000'],
        ]);

        $bom = $this->regionalize($request, $ai->buildBom($data['prompt'], null, $request->user()?->id));
        $project = $this->storeBomProject($request, $bom);

        return redirect('/ai-commerce?prompt=' . urlencode($data['prompt']))
            ->with('status', 'BOM project saved as draft: ' . $project->title . ' (#' . $project->id . ').');
    }

    private function view(Request $request, CommerceAiService $ai, ?string $prompt, ?array $result): Response
    {
        $context = app(GlobalMarketplaceContextService::class)->context($request);
        $title = 'AI BOM Builder for Electronics, Robotics and IoT Projects | NeoGiga';
        $description = 'Build regional BOMs with NeoGiga local AI rules, RFQ handoff, LMS guidance, marketplace pricing and warehouse stock recommendations.';
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'NeoGiga AI BOM Builder',
            'applicationCategory' => 'EngineeringApplication',
            'operatingSystem' => 'Web',
            'description' => $description,
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => $context['currency_code'] ?? 'USD'],
        ];

        return response()->view('frontend.ai-commerce', [
            'title' => $title,
            'description' => $description,
            'jsonLd' => $jsonLd,
            'examples' => $ai->examples(),
            'prompt' => $prompt,
            'result' => $result,
            'marketplaceContext' => $context,
        ]);
    }

    private function regionalize(Request $request, array $bom): array
    {
        $context = app(GlobalMarketplaceContextService::class)->context($request);
        $regional = app(RegionalCommerceService::class);

        $bom['marketplace'] = [
            'name' => $context['current']->name ?? 'NeoGiga Global',
            'code' => strtolower((string) ($context['current']->code ?? 'global')),
            'currency_code' => $context['currency_code'] ?? 'USD',
            'country_code' => $context['country_code'] ?? null,
        ];

        $bom['items'] = collect($bom['items'] ?? [])->map(function (array $item) use ($regional, $context) {
            $stock = null;
            if (! empty($item['product_id'])) {
                $product = Product::find((int) $item['product_id']);
                if ($product) {
                    $stock = $regional->productRegionalSummary(
                        $product,
                        $context['current']->id ?? null,
                        $context['country_id'] ?? null
                    );
                }
            }

            $item['regional_stock'] = $stock ?: ['status' => 'generic_suggestion', 'available' => 0];
            $item['rfq_ready'] = true;

            return $item;
        })->all();

        $bom['rfq_message'] = $this->rfqMessage($bom);
        $bom['source_notes'] = ($bom['source_notes'] ?? 'Local NeoGiga rule engine.') . ' Regional context added from current marketplace and stock overlays.';
        $bom['confidence_level'] = $bom['confidence_level'] ?? 'medium';
        $bom['last_updated'] = now()->toISOString();
        $bom['disclaimer'] = 'Advisory only. Review compatibility, source notes, confidence, regional stock and seller terms before ordering.';

        return $bom;
    }

    private function rfqMessage(array $bom): string
    {
        $lines = [$bom['title'] ?? 'NeoGiga AI BOM', '', 'Requested BOM:'];

        foreach ($bom['items'] ?? [] as $item) {
            $lines[] = '- ' . ($item['name'] ?? 'Item') . ' x ' . ($item['quantity'] ?? 1) . ' - ' . ($item['reason'] ?? 'Suggested by NeoGiga AI');
        }

        $lines[] = '';
        $lines[] = 'Source notes: ' . ($bom['source_notes'] ?? 'Local NeoGiga rule engine.');
        $lines[] = 'Confidence: ' . ($bom['confidence_level'] ?? 'medium');
        $lines[] = 'Advisory only.';

        return implode("\n", $lines);
    }

    private function storeBomProject(Request $request, array $bom): BomProject
    {
        $context = app(GlobalMarketplaceContextService::class)->context($request);

        return DB::transaction(function () use ($request, $bom, $context) {
            $baseSlug = Str::slug($bom['title'] ?? 'ai-bom-project') ?: 'ai-bom-project';
            $slug = $baseSlug . '-' . Str::lower(Str::random(6));

            $project = BomProject::create([
                'marketplace_id' => $context['current']->id ?? null,
                'country_id' => $context['country_id'] ?? null,
                'title' => $bom['title'] ?? 'AI BOM Project',
                'slug' => $slug,
                'difficulty' => 'beginner',
                'estimated_build_time' => 'Staff review pending',
                'description' => 'Generated by NeoGiga local AI BOM builder from prompt: ' . mb_substr((string) ($bom['prompt'] ?? ''), 0, 500),
                'safety_notes' => 'Advisory only. Confirm electrical ratings, compatibility, supplier terms, and safety requirements before build.',
                'required_tools' => ['multimeter', 'soldering kit', 'wire cutter', 'bench power supply'],
                'is_public' => false,
                'status' => 'draft',
                'seo_meta' => [
                    'title' => ($bom['title'] ?? 'AI BOM Project') . ' | NeoGiga',
                    'description' => 'Draft AI-generated BOM project saved for staff review.',
                ],
                'metadata' => [
                    'source' => 'public_ai_commerce',
                    'engine' => 'local_rule_engine',
                    'marketplace' => $bom['marketplace'] ?? null,
                    'source_notes' => $bom['source_notes'] ?? null,
                    'confidence_level' => $bom['confidence_level'] ?? 'medium',
                    'last_updated' => $bom['last_updated'] ?? now()->toISOString(),
                    'advisory' => 'Advisory only',
                    'prompt' => $bom['prompt'] ?? null,
                    'session_id' => $request->session()->getId(),
                ],
            ]);

            foreach (($bom['items'] ?? []) as $index => $item) {
                $project->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'category_id' => null,
                    'name' => $item['name'] ?? 'BOM item',
                    'required_or_optional' => 'required',
                    'quantity' => (float) ($item['quantity'] ?? 1),
                    'reason' => $item['reason'] ?? null,
                    'substitute_allowed' => true,
                    'priority' => ($index + 1) * 10,
                    'notes' => json_encode([
                        'availability_status' => $item['availability_status'] ?? null,
                        'regional_stock' => $item['regional_stock'] ?? null,
                        'warranty_note' => $item['warranty_note'] ?? null,
                    ]),
                ]);
            }

            return $project;
        });
    }
}
