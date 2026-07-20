<?php

return [
    /*
    | Default institutional discount percentages by account type.
    | Admin can override per marketplace via pricing rules (customer_segment scope)
    | or marketplace-specific keys below.
    */
    'discounts' => [
        'government' => (float) env('B2B_DISCOUNT_GOVERNMENT', 12),
        'school' => (float) env('B2B_DISCOUNT_SCHOOL', 15),
        'corporate' => (float) env('B2B_DISCOUNT_CORPORATE', 5),
        'ngo' => (float) env('B2B_DISCOUNT_NGO', 8),
        'hospital' => (float) env('B2B_DISCOUNT_HOSPITAL', 10),
        'other' => (float) env('B2B_DISCOUNT_OTHER', 0),
    ],

    'account_types' => [
        'corporate' => 'Corporate / Business',
        'government' => 'Government Agency',
        'school' => 'School / Educational Institution',
        'ngo' => 'NGO / Non-profit',
        'hospital' => 'Hospital / Healthcare',
        'other' => 'Other Institution',
    ],

    'required_documents' => [
        'government' => ['document_company_reg', 'document_tax_certificate', 'document_institutional_id'],
        'school' => ['document_company_reg', 'document_institutional_id'],
        'corporate' => ['document_company_reg', 'document_tax_certificate'],
        'ngo' => ['document_company_reg', 'document_institutional_id'],
        'hospital' => ['document_company_reg', 'document_tax_certificate', 'document_institutional_id'],
        'other' => ['document_company_reg'],
    ],
];
