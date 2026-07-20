<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('distributor_support_tickets')) {
            Schema::create('distributor_support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained('distributors')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('ticket_number')->unique();
                $table->string('subject');
                $table->text('body')->nullable();
                $table->string('status')->default('open')->index();
                $table->string('priority')->default('normal');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('distributor_support_tickets');
    }
};
