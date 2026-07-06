<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class LandingController extends Controller
{
    /**
     * NeoGiga landing page (SSR Blade — SEO foundation, Blueprint §42).
     * The category grid is static config so the page renders even before
     * the database is seeded; it links into the catalog API.
     */
    public function __invoke(): Response
    {
        $categories = [
            ['name' => 'Semiconductors', 'slug' => 'semiconductors', 'icon' => '⚡', 'blurb' => 'ICs, MCUs, discretes, memory and logic from leading manufacturers.'],
            ['name' => 'Electronics', 'slug' => 'electronic-components', 'icon' => '🔌', 'blurb' => 'Passives, connectors, displays and every board-level component.'],
            ['name' => 'IoT & Wireless', 'slug' => 'iot-wireless', 'icon' => '📡', 'blurb' => 'WiFi, BLE, LoRa and cellular modules for connected products.'],
            ['name' => 'Robotics', 'slug' => 'robotics', 'icon' => '🦾', 'blurb' => 'Motors, drivers, actuators, chassis and robot controllers.'],
            ['name' => 'Industrial Automation', 'slug' => 'industrial-automation', 'icon' => '🏭', 'blurb' => 'PLCs, HMIs, sensors, relays and factory control systems.'],
            ['name' => 'Battery Technology', 'slug' => 'battery-technology', 'icon' => '🔋', 'blurb' => 'Cells, packs, BMS and charging for every chemistry.'],
            ['name' => 'Power Storage', 'slug' => 'power-storage', 'icon' => '⚙️', 'blurb' => 'Energy storage systems, inverters and power conversion.'],
            ['name' => 'AI Hardware', 'slug' => 'ai-hardware', 'icon' => '🧠', 'blurb' => 'Edge AI boards, accelerators, vision kits and dev platforms.'],
            ['name' => 'Engineering Tools', 'slug' => 'engineering-tools', 'icon' => '🛠️', 'blurb' => 'Test & measurement, soldering, 3D printing and lab gear.'],
        ];

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => 'https://neogiga.com/#organization',
                    'name' => config('seo.site_name'),
                    'legalName' => config('seo.organization.legal_name'),
                    'url' => 'https://neogiga.com',
                    'email' => config('seo.organization.email'),
                    'sameAs' => array_values(array_filter([
                        config('seo.social.twitter'),
                        config('seo.social.facebook'),
                        config('seo.social.instagram'),
                        config('seo.social.linkedin'),
                        config('seo.social.youtube'),
                        config('seo.social.github'),
                    ])),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => url('/') . '/#website',
                    'url' => url('/'),
                    'name' => config('seo.site_name'),
                    'publisher' => ['@id' => 'https://neogiga.com/#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => url('/api/v1/products/search') . '?q={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [[
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => url('/'),
                    ]],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'What is NeoGiga?',
                            'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'NeoGiga is a global engineering ecosystem combining an electronics and industrial marketplace, learning resources (LMS), community projects and AI-assisted commerce, with regional editions including neogiga.in for India and giganepal.com for Nepal.'],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'How do I sell on NeoGiga?',
                            'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Distributors, manufacturers and local shops can apply as vendors. Each vendor is approved per regional marketplace, then manages offers, stock and settlement through the seller tools.'],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Which countries does NeoGiga serve?',
                            'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'NeoGiga launches with a global edition (neogiga.com), India (neogiga.in) and Nepal (giganepal.com), with more regional marketplaces planned.'],
                        ],
                    ],
                ],
            ],
        ];

        return response()
            ->view('landing', [
                'categories' => $categories,
                'jsonLd' => $jsonLd,
                'locale' => 'en-IN',
            ])
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=600');
    }
}
