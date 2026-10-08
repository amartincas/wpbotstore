<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            // Marks the one template per store used to alert the admin when
            // a new Lead is created, same pattern as is_reengagement.
            $table->boolean('is_lead_alert')->default(false)->after('is_reengagement');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn('is_lead_alert');
        });
    }
};
