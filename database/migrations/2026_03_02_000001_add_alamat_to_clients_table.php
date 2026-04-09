<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('alamat')->nullable()->after('no_contact');
        });

        // Convert tahun_audit from year to date
        // First add a temporary column
        Schema::table('clients', function (Blueprint $table) {
            $table->date('tahun_audit_new')->nullable()->after('alamat');
        });

        // Migrate existing year data to date format
        DB::table('clients')->whereNotNull('tahun_audit')->orderBy('id')->each(function ($client) {
            DB::table('clients')->where('id', $client->id)->update([
                'tahun_audit_new' => $client->tahun_audit . '-01-01',
            ]);
        });

        // Drop old column and rename new one
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('tahun_audit');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->renameColumn('tahun_audit_new', 'tahun_audit');
        });
    }

    public function down(): void
    {
        // Revert date back to year
        Schema::table('clients', function (Blueprint $table) {
            $table->year('tahun_audit_old')->nullable()->after('no_contact');
        });

        DB::table('clients')->whereNotNull('tahun_audit')->orderBy('id')->each(function ($client) {
            DB::table('clients')->where('id', $client->id)->update([
                'tahun_audit_old' => date('Y', strtotime($client->tahun_audit)),
            ]);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('tahun_audit');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->renameColumn('tahun_audit_old', 'tahun_audit');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('alamat');
        });
    }
};
