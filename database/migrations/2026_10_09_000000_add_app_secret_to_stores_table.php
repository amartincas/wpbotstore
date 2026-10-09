<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Meta App Secret (from Meta for Developers > App Settings > Basic),
            // used to verify the X-Hub-Signature-256 header on every incoming
            // webhook POST. Without this, the webhook endpoint has no real
            // authentication — the store is resolved purely from
            // phone_number_id inside the payload body, which isn't secret.
            $table->string('wa_app_secret')->nullable()->after('wa_verify_token');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('wa_app_secret');
        });
    }
};
