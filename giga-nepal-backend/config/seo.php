<?php

/*
|--------------------------------------------------------------------------
| NeoGiga SEO configuration (Blueprint §42–43)
|--------------------------------------------------------------------------
| Central place for titles, descriptions, social identity and the
| hreflang cluster. Page templates read from here; per-entity overrides
| come from the seo_meta JSON columns on catalog models.
*/

return [

    'site_name' => 'NeoGiga',
    'tagline' => 'Engineering the Future',

    'default_title' => 'NeoGiga — Engineering the Future | Semiconductors, Electronics, IoT, Robotics',
    'title_template' => '%s | NeoGiga',
    'default_description' => 'Source electronics, semiconductors, IoT, robotics and industrial components on the NeoGiga marketplace. Browse verified products or request bulk quotes.',

    'social' => [
        'handle' => '@NeoGigaGlobal',
        'twitter' => 'https://x.com/NeoGigaGlobal',
        'facebook' => 'https://www.facebook.com/NeoGigaGlobal',
        'instagram' => 'https://www.instagram.com/NeoGigaGlobal',
        'linkedin' => 'https://www.linkedin.com/company/neogiga',
        'youtube' => 'https://www.youtube.com/@NeoGigaGlobal',
        'github' => 'https://github.com/NeoGiga',
    ],

    'organization' => [
        'legal_name' => 'Giga Ventures Pvt. Ltd.',
        'email' => 'hello@neogiga.com',
    ],

    /*
    | ccTLD editions (Blueprint §17, §42 international SEO).
    | x-default points at the global .com hub. Never force geo-redirects.
    */
    'editions' => [
        'x-default' => ['domain' => 'https://neogiga.com', 'locale' => 'en'],
        'en' => ['domain' => 'https://neogiga.com', 'locale' => 'en'],
        'en-IN' => ['domain' => 'https://neogiga.in', 'locale' => 'en-IN'],
        'en-NP' => ['domain' => 'https://giganepal.com', 'locale' => 'en-NP'],
        // Phase 2: 'hi-IN', 'ne-NP' content locales.
    ],

    'default_og_image' => '/images/og/neogiga-default.png',
];
