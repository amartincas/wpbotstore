<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Meta's click-to-WhatsApp ad click id, read from the webhook's
            // referral payload. Needed to attribute Conversions API events
            // back to the ad that started the conversation.
            $table->string('ctwa_clid')->nullable()->after('current_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('ctwa_clid');
        });
    }
};
