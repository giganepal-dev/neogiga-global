<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplaces', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplaces', 'welcome_messages')) {
                $table->json('welcome_messages')->nullable();
            }
            if (! Schema::hasColumn('marketplaces', 'welcome_enabled')) {
                $table->boolean('welcome_enabled')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplaces', function (Blueprint $table) {
            if (Schema::hasColumn('marketplaces', 'welcome_messages')) {
                $table->dropColumn('welcome_messages');
            }
            if (Schema::hasColumn('marketplaces', 'welcome_enabled')) {
                $table->dropColumn('welcome_enabled');
            }
        });
    }
};
