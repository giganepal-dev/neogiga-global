<?php

namespace Database\Seeders;

use App\Models\Marketplace\ProductCategory;
use App\Models\CategorySpecTemplate;
use App\Models\SpecTemplateField;
use App\Models\SpecificationGroup;
use Illuminate\Database\Seeder;

class ProductSpecTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating product specification templates...');

        // Get first few categories to attach templates to
        $categories = ProductCategory::take(5)->get();
        
        if ($categories->isEmpty()) {
            $this->command->warn('No product categories found. Please seed categories first.');
            return;
        }

        $count = 0;

        foreach ($categories as $index => $category) {
            $templateName = match($index) {
                0 => 'General Electronics Specifications',
                1 => 'Battery & Power Specifications',
                2 => 'Solar & Energy Specifications',
                3 => 'Motor & Actuator Specifications',
                default => 'General Product Specifications',
            };

            $template = CategorySpecTemplate::create([
                'category_id' => $category->id,
                'name' => $templateName,
                'description' => "Technical specifications for {$category->name}",
                'is_required' => $index < 3,
                'sort_order' => $index + 1,
            ]);

            // Create some common spec fields
            SpecTemplateField::create([
                'template_id' => $template->id,
                'field_name' => 'weight',
                'field_label' => 'Weight',
                'field_type' => 'text',
                'unit' => 'kg',
                'is_required' => false,
                'sort_order' => 1,
            ]);

            SpecTemplateField::create([
                'template_id' => $template->id,
                'field_name' => 'dimensions',
                'field_label' => 'Dimensions',
                'field_type' => 'text',
                'unit' => 'mm',
                'is_required' => false,
                'sort_order' => 2,
            ]);

            SpecTemplateField::create([
                'template_id' => $template->id,
                'field_name' => 'material',
                'field_label' => 'Material',
                'field_type' => 'text',
                'is_required' => false,
                'sort_order' => 3,
            ]);

            // Create spec group
            $group = SpecificationGroup::create([
                'category_id' => $category->id,
                'name' => 'General Specifications',
                'sort_order' => 1,
                'is_expanded' => true,
            ]);

            $count++;
        }

        $this->command->info("✓ Created {$count} specification templates with fields and groups.");
    }
}
