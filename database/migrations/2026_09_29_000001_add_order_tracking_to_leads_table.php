<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Re-engagement conversation state (e.g. 'waiting_customer'). This
            // column was already referenced by WhatsAppController.php and
            // WhatsAppChatCenter.php but never migrated — those calls would
            // fail with a SQL error the first time a re-engagement template
            // was sent.
            $table->string('status')->nullable()->after('bot_active');

            // Order fulfillment lifecycle, distinct from the re-engagement
            // 'status' above. Injected into the AI prompt so the bot doesn't
            // restart the sales flow on a customer with an existing order.
            $table->string('order_status')->nullable()->after('status');
            $table->string('tracking_number')->nullable()->after('order_status');
            $table->string('carrier')->nullable()->after('tracking_number');

            // Flags a lead whose delivery address is missing a city/region,
            // for staff to follow up on — doesn't block lead creation.
            $table->boolean('needs_address_review')->default(false)->after('carrier');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['status', 'order_status', 'tracking_number', 'carrier', 'needs_address_review']);
        });
    }
};
