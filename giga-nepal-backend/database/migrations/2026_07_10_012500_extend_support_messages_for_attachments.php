<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_ticket_messages')) {
            return;
        }

        Schema::table('support_ticket_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('support_ticket_messages', 'attachment_disk')) {
                $table->string('attachment_disk')->nullable();
            }
            if (! Schema::hasColumn('support_ticket_messages', 'attachment_path')) {
                $table->string('attachment_path')->nullable();
            }
            if (! Schema::hasColumn('support_ticket_messages', 'attachment_original_name')) {
                $table->string('attachment_original_name')->nullable();
            }
            if (! Schema::hasColumn('support_ticket_messages', 'attachment_mime_type')) {
                $table->string('attachment_mime_type')->nullable();
            }
            if (! Schema::hasColumn('support_ticket_messages', 'attachment_size')) {
                $table->unsignedBigInteger('attachment_size')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Additive live upgrade: keep uploaded attachment metadata.
    }
};
