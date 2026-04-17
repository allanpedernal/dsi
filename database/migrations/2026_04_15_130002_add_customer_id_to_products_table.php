<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'customer_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('category_id')->index('products_customer_id_index');
                $table->foreign('customer_id', 'products_customer_id_foreign')
                    ->references('id')->on('customers')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'customer_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('customer_id');
            });
        }
    }
};
