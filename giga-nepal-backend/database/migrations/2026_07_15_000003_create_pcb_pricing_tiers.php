<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pcb_pricing_tiers')) {
            Schema::create('pcb_pricing_tiers', function (Blueprint $table) {
                $table->id();
                $table->string('tier_key')->unique(); // '2_layer_fr4_standard'
                $table->string('label');               // '2-Layer FR-4 Standard'
                $table->unsignedSmallInteger('min_layers')->default(1);
                $table->unsignedSmallInteger('max_layers')->default(2);
                $table->string('board_material')->default('FR-4'); // FR-4, Flex, Aluminum, Rogers
                $table->unsignedInteger('min_quantity')->default(5);
                $table->unsignedInteger('max_quantity')->default(1000000);
                $table->decimal('min_length_mm', 8, 2)->default(1);
                $table->decimal('max_length_mm', 8, 2)->default(600);
                $table->decimal('min_width_mm', 8, 2)->default(1);
                $table->decimal('max_width_mm', 8, 2)->default(600);
                $table->decimal('base_fabrication_price', 10, 2);
                $table->decimal('price_per_sq_cm', 10, 4)->default(0.02);
                $table->decimal('price_per_layer', 10, 4)->default(0);
                $table->decimal('engineering_fee', 10, 2)->default(5);
                $table->decimal('setup_fee', 10, 2)->default(8);
                $table->json('surcharge_rates')->nullable();
                $table->unsignedSmallInteger('lead_time_days')->default(7);
                $table->boolean('active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pcb_pricing_tiers');
    }
};
