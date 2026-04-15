<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('activitylog.table_name', 'activity_log');

        if (! Schema::hasColumn($table, 'attribute_changes')) {
            Schema::table($table, function (Blueprint $table) {
                $table->json('attribute_changes')->nullable()->after('event');
            });
        }
    }

    public function down(): void
    {
        $table = config('activitylog.table_name', 'activity_log');

        if (Schema::hasColumn($table, 'attribute_changes')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('attribute_changes');
            });
        }
    }
};
