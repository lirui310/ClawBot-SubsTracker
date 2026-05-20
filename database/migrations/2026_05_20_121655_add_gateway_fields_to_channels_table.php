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
            // iLink may route this bot to a different API server — store it per-channel
            $table->string('baseurl')->default('https://ilinkai.weixin.qq.com')->after('bot_id');
            // Pagination cursor for /ilink/bot/getupdates — must be persisted across polls
            $table->text('poll_buf')->nullable()->after('baseurl');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['baseurl', 'poll_buf']);
        });
    }
};
