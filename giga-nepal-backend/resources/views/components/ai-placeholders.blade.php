@props(['surface' => 'floating_ai_assistant'])

@php
    $surfaces = [
        'floating_ai_assistant' => ['title' => 'AI Assistant', 'context' => 'General engineering and catalog help'],
        'product_page_ai_assistant' => ['title' => 'Product AI Assistant', 'context' => 'Product specs, datasheets, alternatives, LMS links'],
        'ai_bom_builder' => ['title' => 'AI BOM Builder', 'context' => 'Project-to-BOM draft with catalog-only price and stock facts'],
        'ai_project_builder' => ['title' => 'AI Project Builder', 'context' => 'Curated templates, components, wiring notes, and safety checks'],
        'ai_pos_chat' => ['title' => 'AI POS Chat', 'context' => 'Cashier support with strict POS permissions'],
        'lms_ai_tutor' => ['title' => 'LMS AI Tutor', 'context' => 'Lesson explanations with course citations'],
        'seller_ai_assistant' => ['title' => 'Seller AI Assistant', 'context' => 'Seller onboarding and catalog quality support'],
        'admin_ai_console' => ['title' => 'Admin AI Console', 'context' => 'Prompts, providers, evals, feedback, and audit review'],
    ];

    $item = $surfaces[$surface] ?? $surfaces['floating_ai_assistant'];
@endphp

<section class="ng-ai-placeholder" data-ai-surface="{{ $surface }}" aria-label="{{ $item['title'] }}">
    <div class="ng-ai-placeholder__header">
        <strong>{{ $item['title'] }}</strong>
        <span>Foundation ready</span>
    </div>
    <p>{{ $item['context'] }}</p>
    <p>No paid AI provider is connected unless the matching environment key is configured.</p>
</section>
