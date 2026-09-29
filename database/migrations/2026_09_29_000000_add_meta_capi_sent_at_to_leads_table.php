<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Marks when this lead was processed for Meta Conversions API
            // (whether it was actually sent, or skipped for lack of a
            // ctwa_clid/credentials) — prevents double-sending between the
            // live LeadObserver and the one-off backfill command.
            $table->timestamp('meta_capi_sent_at')->nullable()->after('ctwa_clid');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('meta_capi_sent_at');
        });
    }
};
