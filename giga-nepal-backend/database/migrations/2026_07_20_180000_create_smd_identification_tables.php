<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smd_packages', function (Blueprint $table) {
            $table->id();
            $table->string('canonical_name');
            $table->string('normalized_name');
            $table->json('aliases')->nullable();
            $table->string('package_family')->nullable();
            $table->string('mounting_type')->nullable(); // SMD, through-hole
            $table->unsignedSmallInteger('pin_count')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('product_images')->nullOnDelete();
            $table->string('source_url')->nullable();
            $table->string('verification_status')->default('unverified');
            $table->timestamps();

            $table->unique('normalized_name');
        });

        Schema::create('smd_marking_codes', function (Blueprint $table) {
            $table->id();
            $table->string('normalized_marking');  // uppercase search key
            $table->string('display_marking');     // original case/punctuation
            $table->unsignedSmallInteger('marking_length');
            $table->string('first_character', 1);
            $table->string('first_two_characters', 2);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();

            $table->unique(['normalized_marking', 'source_id']);
            $table->index('normalized_marking');
            $table->index('first_character');
        });

        Schema::create('smd_marking_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smd_marking_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->nullOnDelete();
            $table->string('candidate_mpn');
            $table->string('normalized_mpn');
            $table->foreignId('package_id')->nullable()->constrained('smd_packages')->nullOnDelete();
            $table->string('package_text')->nullable();        // raw text from source
            $table->string('component_function')->nullable();
            $table->text('characteristic_text')->nullable();
            $table->unsignedTinyInteger('match_confidence')->default(0); // 0-100
            $table->json('confidence_factors')->nullable();   // breakdown of score
            $table->string('verification_status')->default('unverified');
            $table->string('source_url')->nullable();
            $table->string('source_hash', 64)->nullable();    // sha256 of raw source row
            $table->timestamp('retrieved_at')->nullable();
            $table->timestamps();

            $table->unique(['smd_marking_code_id', 'normalized_mpn', 'manufacturer_id', 'source_hash']);
            $table->index('normalized_mpn');
            $table->index('verification_status');
        });

        Schema::create('smd_identification_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('marking_query');
            $table->string('package_query')->nullable();
            $table->string('manufacturer_query')->nullable();
            $table->string('function_query')->nullable();
            $table->text('board_context')->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->unsignedBigInteger('selected_match_id')->nullable();
            $table->timestamps();
        });

        // Seed common SMD packages
        $this->seedPackages();
    }

    private function seedPackages(): void
    {
        $packages = [
            ['SOD-123', 'SOD-123', ['SOD123'], 'SOD', 'SMD', 2],
            ['SOD-323', 'SOD-323', ['SOD323'], 'SOD', 'SMD', 2],
            ['SOD-523', 'SOD-523', ['SOD523'], 'SOD', 'SMD', 2],
            ['SOT-23', 'SOT-23', ['SOT23', 'SOT-23-3'], 'SOT', 'SMD', 3],
            ['SOT-23-5', 'SOT-23-5', ['SOT23-5', 'SOT-25', 'SSOP-5'], 'SOT', 'SMD', 5],
            ['SOT-23-6', 'SOT-23-6', ['SOT23-6', 'SOT-26'], 'SOT', 'SMD', 6],
            ['SOT-89', 'SOT-89', ['SOT89'], 'SOT', 'SMD', 3],
            ['SOT-223', 'SOT-223', ['SOT223'], 'SOT', 'SMD', 4],
            ['SC-70', 'SC-70', ['SC70'], 'SC', 'SMD', 3],
            ['SC-82AB', 'SC-82AB', ['SC82AB'], 'SC', 'SMD', 4],
            ['SC-88A', 'SC-88A', ['SC88A'], 'SC', 'SMD', 5],
            ['DFN1010-4', 'DFN1010-4', ['DFN1010'], 'DFN', 'SMD', 4],
            ['UMT3', 'UMT3', [], 'UMT', 'SMD', 3],
            ['USP-4', 'USP-4', ['USP4'], 'USP', 'SMD', 4],
            ['SMA', 'SMA', ['DO-214AC'], 'SMA', 'SMD', 2],
            ['SMB', 'SMB', ['DO-214AA'], 'SMB', 'SMD', 2],
            ['SMC', 'SMC', ['DO-214AB'], 'SMC', 'SMD', 2],
        ];

        $now = now();
        foreach ($packages as [$canonical, $normalized, $aliases, $family, $mounting, $pins]) {
            DB::table('smd_packages')->insertOrIgnore([
                'canonical_name' => $canonical,
                'normalized_name' => $normalized,
                'aliases' => json_encode($aliases),
                'package_family' => $family,
                'mounting_type' => $mounting,
                'pin_count' => $pins,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('smd_identification_searches');
        Schema::dropIfExists('smd_marking_matches');
        Schema::dropIfExists('smd_marking_codes');
        Schema::dropIfExists('smd_packages');
    }
};
