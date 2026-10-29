<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Freshful admin console (admin update 2, 23 Oct 2026). New tables only; no existing table is changed.
|   support_tickets / support_ticket_messages : support tickets, disputes and enquiry replies (with the full conversation)
|   admin_refunds                             : refunds owed to customers and how they were paid back
|   hub_area_meta                             : extra delivery-area details (circle radius) the old hubs table has no column for
|   admin_review_replies                      : admin replies and hide / show on ratings & reviews
*/
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('support_tickets')) Schema::create('support_tickets', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('code', 20)->unique();
            $t->string('kind', 12)->default('ticket');          // ticket | dispute | enquiry
            $t->string('source', 20)->default('manual');        // manual | grievance | contact | franchise | refund | order
            $t->unsignedBigInteger('source_id')->nullable();
            $t->unsignedBigInteger('buyer_id')->nullable();
            $t->string('order_id', 40)->nullable();
            $t->unsignedInteger('hub_id')->nullable();
            $t->string('contact_name', 120)->default('');
            $t->string('contact_phone', 20)->default('');
            $t->string('contact_email', 150)->default('');
            $t->string('category', 80)->default('');
            $t->string('subject', 200)->default('');
            $t->text('body')->nullable();
            $t->string('status', 20)->default('open');          // open | in_progress | waiting | resolved | closed
            $t->string('priority', 10)->default('normal');      // low | normal | high | urgent
            $t->string('assigned_to', 120)->default('');
            $t->string('outcome', 30)->default('');             // disputes: refund | partial | rejected | replaced | other
            $t->decimal('claim_amount', 10, 2)->default(0);
            $t->decimal('settled_amount', 10, 2)->default(0);
            $t->timestamp('due_at')->nullable();
            $t->timestamp('first_reply_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->string('created_by', 120)->default('');
            $t->timestamps();
            $t->unique(['source', 'source_id']);
            $t->index(['kind', 'status']);
            $t->index('order_id');
        });
        if (!Schema::hasTable('support_ticket_messages')) Schema::create('support_ticket_messages', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('ticket_id');
            $t->string('author_type', 12)->default('staff');    // staff | customer | note | system
            $t->string('author', 120)->default('');
            $t->text('body');
            $t->string('channel', 12)->default('note');         // note | email | whatsapp | sms | phone
            $t->string('send_status', 20)->default('');         // sent | not_sent | opened_whatsapp
            $t->timestamp('created_at')->useCurrent();
            $t->index('ticket_id');
        });
        if (!Schema::hasTable('admin_refunds')) Schema::create('admin_refunds', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('order_id', 40);
            $t->string('reason', 20);                           // cancelled | weight | dispute | manual
            $t->unsignedBigInteger('buyer_id')->nullable();
            $t->unsignedInteger('hub_id')->nullable();
            $t->decimal('amount', 10, 2);
            $t->string('method', 12)->default('');              // wallet | upi | bank | cash | gateway
            $t->string('status', 12)->default('pending');       // pending | done | rejected
            $t->string('reference', 120)->default('');
            $t->string('note', 255)->default('');
            $t->unsignedBigInteger('ticket_id')->nullable();
            $t->string('by_name', 120)->default('');
            $t->timestamps();
            $t->unique(['order_id', 'reason']);
            $t->index('status');
        });
        if (!Schema::hasTable('hub_area_meta')) Schema::create('hub_area_meta', function (Blueprint $t) {
            $t->unsignedInteger('hub_id')->primary();
            $t->unsignedInteger('radius_m')->nullable();
            $t->string('drawn_as', 12)->default('');            // polygon | box | circle | point
            $t->timestamp('updated_at')->nullable();
        });
        if (!Schema::hasTable('admin_review_replies')) Schema::create('admin_review_replies', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('table_name', 60);
            $t->unsignedBigInteger('row_id');
            $t->text('reply')->nullable();
            $t->boolean('hidden')->default(false);
            $t->string('by_name', 120)->default('');
            $t->timestamps();
            $t->unique(['table_name', 'row_id']);
        });
    }

    public function down(): void
    {
        foreach (['admin_review_replies', 'hub_area_meta', 'admin_refunds', 'support_ticket_messages', 'support_tickets'] as $t) Schema::dropIfExists($t);
    }
};
