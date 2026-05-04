<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('ai_sales_strategy')->nullable()->after('type')->comment('AI sales strategy for this product');
            $table->text('faq_context')->nullable()->after('ai_sales_strategy')->comment('FAQ and operational context for this product');
            $table->string('required_customer_info')->nullable()->after('faq_context')->comment('Required customer info to collect for this product');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['ai_sales_strategy', 'faq_context', 'required_customer_info']);
        });
    }
};
