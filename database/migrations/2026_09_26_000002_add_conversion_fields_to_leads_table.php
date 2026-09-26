<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('store_id')->constrained()->nullOnDelete();
            // Snapshot of products.price at the moment the lead was created,
            // used as the value for the Meta Conversions API "Purchase" event.
            $table->decimal('sale_value', 10, 2)->nullable()->after('summary');
            $table->string('ctwa_clid')->nullable()->after('sale_value');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn(['sale_value', 'ctwa_clid']);
        });
    }
};
