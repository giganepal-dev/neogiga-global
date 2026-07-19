<?php

// Footer information pages. Each key is the public slug; rendered by
// frontend.pages.static via the pages.static route in routes/web.php.
return [
    'about' => [
        'title' => 'About NeoGiga',
        'intro' => 'NeoGiga is a global engineering marketplace for semiconductors, electronic components, IoT, robotics, automation, battery technology and industrial tools — with regional editions across Asia-Pacific and beyond.',
        'sections' => [
            ['h' => 'What we do', 'p' => ['We connect engineers, manufacturers, distributors and sellers on one platform: a catalog of 600,000+ parts with datasheets and specifications, regional pricing and stock, BOM matching, RFQ sourcing, and PCB fabrication services through pcb.neogiga.com.']],
            ['h' => 'Regional editions', 'p' => ['NeoGiga operates a global master marketplace at neogiga.com plus country editions with local currency and stock visibility. Your selected edition is remembered and drives pricing, availability and delivery estimates.']],
            ['h' => 'For sellers and partners', 'p' => ['Verified sellers, distributors and manufacturers can list products, receive RFQs and manage orders through dedicated portals. See Become a Seller in the footer to apply.']],
        ],
    ],
    'contact' => [
        'title' => 'Contact Us',
        'intro' => 'The fastest way to reach the right team is through the tools built into the platform.',
        'sections' => [
            ['h' => 'Sourcing and quotes', 'p' => ['For part sourcing, bulk pricing or availability questions, open an RFQ from any product page or from the RFQ tool in the navigation. Our sourcing team responds to every request.']],
            ['h' => 'Sellers and partnerships', 'p' => ['Apply through the Become a Seller page. Existing partners can use the Seller or Distributor portal message channels.']],
            ['h' => 'Feedback', 'p' => ['Spotted a data error on a product page? Use the report link on that page, or subscribe to the newsletter in the footer and reply to any edition — every message is read.']],
        ],
    ],
    'quality-assurance' => [
        'title' => 'Quality Assurance',
        'intro' => 'Every part sold on NeoGiga is traceable to its source catalog, brand and manufacturer record.',
        'sections' => [
            ['h' => 'Sourcing controls', 'p' => ['Catalog data is imported from authorized supplier feeds with staged review: brand mapping, category mapping, image validation and datasheet checks happen before a product is published.']],
            ['h' => 'Data accuracy', 'p' => ['Specifications shown on product pages come from manufacturer documentation. If a discrepancy is found, the listing is corrected or unpublished pending review.']],
            ['h' => 'Returns for quality issues', 'p' => ['Parts that arrive damaged or not matching their listing are covered by our Return Policy — see Returns in the footer.']],
        ],
    ],
    'how-to-order' => [
        'title' => 'How to Order',
        'intro' => 'From single prototypes to production volumes, ordering follows the same flow.',
        'sections' => [
            ['h' => '1. Find your parts', 'p' => ['Search by MPN, SKU or keyword, browse categories and brands, or upload a BOM using the Upload BOM button beside the search bar for instant multi-line matching.']],
            ['h' => '2. Check regional price and stock', 'p' => ['Prices and availability shown reflect your selected regional edition. Switch editions from the top bar at any time.']],
            ['h' => '3. Cart, RFQ or quote', 'p' => ['Add items to your cart, or request a quotation for bulk quantities, contract pricing, or parts without published regional pricing. RFQ responses include price, lead time and validity.']],
            ['h' => '4. Checkout and tracking', 'p' => ['Complete checkout with your delivery address and payment selection. Order status, invoices and shipment tracking are available from your account dashboard.']],
        ],
    ],
    'shipping' => [
        'title' => 'International Shipping',
        'intro' => 'NeoGiga ships from regional warehouses and partner distributors to most countries.',
        'sections' => [
            ['h' => 'Delivery estimates', 'p' => ['Regional-stock items typically dispatch within 1–3 business days. Global-stock items transit through our consolidation hubs; estimates are shown at checkout and on RFQ responses.']],
            ['h' => 'Duties and taxes', 'p' => ['Regional editions display pricing consistent with local tax rules where configured. Import duties for cross-border shipments are the responsibility of the recipient unless your quotation states otherwise.']],
            ['h' => 'Tracking', 'p' => ['Every shipment includes a tracking reference visible in your account dashboard once dispatched.']],
        ],
    ],
    'returns' => [
        'title' => 'Return Policy',
        'intro' => 'Returns are accepted for parts that arrive damaged, defective, or not matching their listing.',
        'sections' => [
            ['h' => 'Window', 'p' => ['Report issues within 30 days of delivery from your account dashboard or through the order contact channel.']],
            ['h' => 'Conditions', 'p' => ['Components must be returned in their original packaging. Moisture-sensitive, ESD-sensitive and cut-tape items must remain in unopened original sealed packaging to be eligible unless the return is due to our error.']],
            ['h' => 'Refunds', 'p' => ['Approved returns are refunded to the original payment method after inspection. Where a replacement is preferred and stock is available, we ship replacements first.']],
        ],
    ],
    'payment-terms' => [
        'title' => 'Orders & Payment Terms',
        'intro' => 'Supported payment methods vary by regional edition and are shown at checkout.',
        'sections' => [
            ['h' => 'Payment methods', 'p' => ['Regional editions support local payment gateways alongside international options. Business customers can request account terms through the RFQ and B2B channels.']],
            ['h' => 'Quotations', 'p' => ['RFQ quotations state price, currency, validity period and lead time. Orders placed against an expired quotation are re-validated before confirmation.']],
            ['h' => 'Invoices', 'p' => ['A tax invoice is issued for every completed order and remains downloadable from your account dashboard.']],
        ],
    ],
    'faq' => [
        'title' => 'Frequently Asked Questions',
        'intro' => 'Quick answers to the most common questions.',
        'sections' => [
            ['h' => 'Do prices include tax?', 'p' => ['Each regional edition states whether displayed prices are tax-inclusive. The checkout summary always itemizes tax before you confirm.']],
            ['h' => 'Can I order parts with no published price?', 'p' => ['Yes — use Request Quotation on the product page. Unpriced parts are sourced through the RFQ desk.']],
            ['h' => 'How do I upload a BOM?', 'p' => ['Use the Upload BOM button beside the search bar. Paste or upload your bill of materials and NeoGiga matches lines against the catalog by MPN, SKU and description.']],
            ['h' => 'How do I become a seller or distributor?', 'p' => ['Open Become a Seller from the footer and submit the application form. Applications are reviewed before portal access is granted.']],
            ['h' => 'Where is my order?', 'p' => ['Order status and tracking references are in your account dashboard under Orders.']],
        ],
    ],
    'cookie-notice' => [
        'title' => 'Cookie Notice',
        'intro' => 'NeoGiga uses essential cookies to make the site work, and analytics cookies to understand how it is used.',
        'sections' => [
            ['h' => 'Essential cookies', 'p' => ['Session, cart, security (CSRF) and marketplace-edition preference cookies are required for the platform to function and cannot be disabled.']],
            ['h' => 'Analytics', 'p' => ['We use Google Analytics to measure aggregate page usage. These cookies do not expose your identity to other visitors or sellers.']],
            ['h' => 'Managing cookies', 'p' => ['You can clear or block cookies in your browser settings; essential features such as the cart and login will stop working without them. See the Privacy Policy for the full data-handling picture.']],
        ],
    ],
    'terms' => [
        'title' => 'Terms and Conditions',
        'intro' => 'These terms govern your use of NeoGiga and its regional editions. By browsing, ordering, or listing on the platform you agree to them.',
        'sections' => [
            ['h' => 'Using the platform', 'p' => ['NeoGiga provides catalog data, pricing, BOM matching, RFQ sourcing and PCB services across regional editions. Catalog specifications are drawn from manufacturer documentation and supplier feeds and are provided for engineering reference; verify critical parameters against the manufacturer datasheet before committing to production.']],
            ['h' => 'Orders and pricing', 'p' => ['Prices, availability and delivery estimates reflect your selected regional edition and may change until an order is confirmed. Where a listing contains an obvious error, we may decline or cancel the affected order and refund any payment taken.']],
            ['h' => 'Accounts and conduct', 'p' => ['You are responsible for activity under your account and for keeping your credentials secure. Sellers, distributors and manufacturers additionally agree to the terms of their respective portals. Misuse, fraud or scraping may result in suspension.']],
            ['h' => 'Liability', 'p' => ['The platform is provided on an "as available" basis. To the extent permitted by law, NeoGiga is not liable for indirect or consequential loss arising from catalog data, third-party supplier performance, or service interruptions. Nothing here limits rights that cannot be excluded under applicable law.']],
        ],
    ],
    'privacy' => [
        'title' => 'Privacy Policy',
        'intro' => 'This policy explains what data NeoGiga collects, why, and the choices you have. It applies to neogiga.com and all regional editions.',
        'sections' => [
            ['h' => 'What we collect', 'p' => ['Account details you provide (name, email, delivery and billing information), order and RFQ history, and technical data such as IP address, device and usage analytics needed to operate and secure the platform.']],
            ['h' => 'How we use it', 'p' => ['To process orders and quotations, provide regional pricing and stock, prevent fraud, respond to support requests, and improve the platform. We do not sell your personal data.']],
            ['h' => 'Sharing', 'p' => ['Data is shared with the sellers, distributors and logistics providers needed to fulfil an order, and with payment processors to take payment. Suppliers see only what is required to complete your request, never your full profile.']],
            ['h' => 'Your choices', 'p' => ['You can access, correct or request deletion of your account data from your dashboard or by contacting us. See the Cookie Notice for how cookies and analytics are handled, and how to limit them.']],
        ],
    ],
];
