<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_user')) {
            Schema::create('customer_user', function (Blueprint $table) {
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['customer_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_user');
    }
};
