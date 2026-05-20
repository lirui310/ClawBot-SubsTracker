<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('webhook_token', 48)->nullable()->unique()->after('poll_buf');
        });

        // Backfill existing channels
        DB::table('channels')->whereNull('webhook_token')->get()->each(function ($row) {
            DB::table('channels')->where('id', $row->id)->update([
                'webhook_token' => \Illuminate\Support\Str::random(48),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('webhook_token');
        });
    }
};
