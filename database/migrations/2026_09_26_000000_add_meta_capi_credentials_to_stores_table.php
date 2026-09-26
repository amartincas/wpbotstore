<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('meta_dataset_id')->nullable()->after('wa_verify_token');
            $table->text('meta_capi_access_token')->nullable()->after('meta_dataset_id');
            // No default on purpose: a silently-assumed currency (e.g. always COP)
            // would send the wrong value on a Purchase event for stores that sell
            // in USD. Each store must set this explicitly (see StoreForm.php).
            $table->string('meta_capi_currency')->nullable()->after('meta_capi_access_token');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['meta_dataset_id', 'meta_capi_access_token', 'meta_capi_currency']);
        });
    }
};
