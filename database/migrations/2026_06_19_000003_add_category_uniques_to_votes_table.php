<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->unique(['voter_id', 'category_id'], 'votes_voter_category_unique');
            $table->unique(['mac_address', 'category_id'], 'votes_device_category_unique');
            $table->unique(['ip_address', 'category_id'], 'votes_ip_category_unique');
        });
    }

    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique('votes_ip_category_unique');
            $table->dropUnique('votes_device_category_unique');
            $table->dropUnique('votes_voter_category_unique');
        });
    }
};
