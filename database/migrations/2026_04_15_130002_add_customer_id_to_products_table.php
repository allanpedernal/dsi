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
                $table->foreignId('customer_id')->nullable()->after('category_id')
                    ->constrained('customers')->nullOnDelete()->index();
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
