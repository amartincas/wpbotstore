<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            // Meta's message id, needed to match an incoming status webhook
            // (keyed only by wamid) back to this row.
            $table->string('wamid')->nullable()->after('content')->index();

            // Delivery status (pending/sent/delivered/read/failed), stored
            // permanently on the message itself instead of in a cache entry
            // with a TTL — the Chat Center's checkmarks were reverting to
            // "pending" once that TTL (24h) expired, even though the message
            // had actually been delivered/read.
            $table->string('status')->nullable()->after('wamid');
            $table->timestamp('status_updated_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn(['wamid', 'status', 'status_updated_at']);
        });
    }
};
