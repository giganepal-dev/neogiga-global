<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Privacy-masked customer↔seller messaging.
 *
 * Conversations tie an order, RFQ, or support context to two or more
 * participants. Messages are stored with both the raw (sender-visible)
 * body and a masked body that strips PII before the receiver sees it.
 *
 * Masking is enforced server-side in MessagingService; the raw body is
 * never transmitted to a non-admin receiver.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('subject')->nullable();
            $table->nullableMorphs('context');   // order, rfq_request, support_ticket, …
            $table->string('status')->default('open'); // open, closed, archived
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('participant');   // user, vendor, customer, …
            $table->string('role')->default('member'); // owner, member, observer
            $table->string('mask_level')->default('full'); // full | partial | none
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'participant_type', 'participant_id'], 'conv_part_unique');
        });

        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('sender');       // user (customer), vendor (seller), or null (system)
            $table->text('body');                    // original body as sent
            $table->text('body_masked')->nullable(); // PII-masked version for receivers
            $table->string('type')->default('text'); // text, system, attachment
            $table->json('metadata')->nullable();    // attachment info, system event data
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('conversation_messages')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('conversation_messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
