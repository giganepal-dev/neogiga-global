<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('invoice_number');
            $table->string('verification_hash', 64)->nullable()->after('qr_token');
            $table->string('pdf_path', 500)->nullable()->after('notes');
            $table->timestamp('pdf_generated_at')->nullable()->after('pdf_path');
            $table->unsignedBigInteger('credit_note_id')->nullable()->after('pdf_generated_at');
            $table->text('credit_note_reason')->nullable()->after('credit_note_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'qr_token',
                'verification_hash',
                'pdf_path',
                'pdf_generated_at',
                'credit_note_id',
                'credit_note_reason',
            ]);
        });
    }
};
