<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // The encrypted() cast's ciphertext (base64 + IV + MAC) is far
            // longer than the plaintext secret — a varchar(255) truncates
            // it. Same fix already applied to the other encrypted token
            // columns (wa_access_token, meta_capi_access_token, etc).
            $table->text('wa_app_secret')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('wa_app_secret')->nullable()->change();
        });
    }
};
