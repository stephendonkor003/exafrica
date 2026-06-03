<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominations', function (Blueprint $table) {
            $table->json('achievement_documents')->nullable()->after('nomination_reason');
            $table->json('achievement_links')->nullable()->after('achievement_documents');
        });
    }

    public function down(): void
    {
        Schema::table('nominations', function (Blueprint $table) {
            $table->dropColumn(['achievement_links', 'achievement_documents']);
        });
    }
};
