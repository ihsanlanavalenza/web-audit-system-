<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_requests', function (Blueprint $table) {
            $table->timestamp('followup_7day_sent_at')->nullable()->after('followup_sent_at');
            $table->timestamp('followup_15day_sent_at')->nullable()->after('followup_7day_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('data_requests', function (Blueprint $table) {
            $table->dropColumn(['followup_7day_sent_at', 'followup_15day_sent_at']);
        });
    }
};
