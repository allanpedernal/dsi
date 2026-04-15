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

        // Backfill from the old single-FK column so we don't lose existing links,
        // then drop it in favour of the pivot.
        if (Schema::hasColumn('users', 'customer_id')) {
            DB::statement('
                INSERT INTO customer_user (customer_id, user_id, created_at, updated_at)
                SELECT customer_id, id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                FROM users
                WHERE customer_id IS NOT NULL
            ');

            if (DB::getDriverName() === 'mysql') {
                // FK name varies (sometimes literally "1" from a half-run) — discover and drop.
                $fks = DB::select(
                    'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
                       AND REFERENCED_TABLE_NAME IS NOT NULL',
                    ['users', 'customer_id'],
                );
                foreach ($fks as $fk) {
                    DB::statement('ALTER TABLE `users` DROP FOREIGN KEY `'.$fk->CONSTRAINT_NAME.'`');
                }
                Schema::table('users', fn (Blueprint $t) => $t->dropColumn('customer_id'));
            } else {
                // SQLite recreates the table on dropColumn and trips on the orphan FK.
                // Drop both the FK definition and the column in one Blueprint pass.
                Schema::table('users', function (Blueprint $t) {
                    $t->dropForeign(['customer_id']);
                    $t->dropColumn('customer_id');
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('email')
                    ->constrained('customers')->nullOnDelete()->index();
            });
        }

        Schema::dropIfExists('customer_user');
    }
};
