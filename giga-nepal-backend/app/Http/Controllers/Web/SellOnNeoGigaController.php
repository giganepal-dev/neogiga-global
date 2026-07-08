<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class SellOnNeoGigaController extends Controller
{
    public function sell(): Response
    {
        return $this->page('sell-on-neogiga', [
            'mode' => 'seller',
            'title' => 'Sell on NeoGiga | Multi-Country Electronics Marketplace for South Asia',
            'description' => 'Sell electronics, robotics, IoT, batteries, solar and tools across South Asia with NeoGiga multi-marketplace seller platform.',
        ]);
    }

    public function distributors(): Response
    {
        return $this->page('distributors', [
            'mode' => 'distributor',
            'title' => 'NeoGiga Distributor Network | Regional Electronics and Robotics Supply',
            'description' => 'Join NeoGiga distributor network for regional electronics, robotics, automation, solar, EV and maker supply across South Asia.',
        ]);
    }

    public function earlyAccess(): Response
    {
        return $this->page('seller-early-access', [
            'mode' => 'seller',
            'title' => 'Seller Early Access | NeoGiga',
            'description' => 'Apply for NeoGiga seller early access for Nepal and India marketplace onboarding review.',
        ]);
    }

    public function aiCommerce(): Response
    {
        return $this->page('ai-commerce', [
            'mode' => 'ai',
            'title' => 'AI Commerce for Electronics, Robotics and IoT Projects | NeoGiga',
            'description' => 'NeoGiga AI Commerce helps engineers, makers, schools and labs turn project ideas into BOMs, tutorials and buying options.',
        ]);
    }

    private function page(string $view, array $data): Response
    {
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'Organization', '@id' => 'https://neogiga.com/#organization', 'name' => 'NeoGiga', 'url' => 'https://neogiga.com'],
                ['@type' => 'WebPage', 'name' => $data['title'], 'description' => $data['description'], 'url' => url()->current()],
                ['@type' => 'BreadcrumbList', 'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $data['title'], 'item' => url()->current()],
                ]],
                ['@type' => 'FAQPage', 'mainEntity' => [
                    ['@type' => 'Question', 'name' => 'Is the seller portal live?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'The seller portal is launching soon. Early applicants are reviewed first for Nepal and India onboarding.']],
                    ['@type' => 'Question', 'name' => 'Does AI Commerce call a paid AI API?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'The public demo uses a local rule engine unless provider keys are configured later.']],
                ]],
            ],
        ];

        return response()->view('frontend.' . $view, $data + ['jsonLd' => $jsonLd]);
    }
}
