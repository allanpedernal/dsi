<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->enum('source', ['web', 'api', 'console', 'system'])->default('system')->after('event')->index();
            $table->uuid('request_id')->nullable()->after('source')->index();
            $table->string('ip_address', 45)->nullable()->after('request_id');
            $table->string('user_agent')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->dropColumn(['source', 'request_id', 'ip_address', 'user_agent']);
        });
    }
};
