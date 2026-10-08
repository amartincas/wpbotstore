<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // WhatsApp number (E.164, no '+') that receives the "new lead"
            // admin alert template. No user/admin phone field exists yet, so
            // this lives on the store itself — a store may want alerts sent
            // to an ops number rather than any individual admin's phone.
            $table->string('alert_phone')->nullable()->after('meta_capi_currency');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('alert_phone');
        });
    }
};
