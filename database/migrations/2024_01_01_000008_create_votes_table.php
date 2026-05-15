<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nominee_id')->constrained('nominees')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('voter_id')->constrained('voters')->onDelete('cascade');
            $table->string('mac_address');
            $table->enum('vote_type', ['public_vote', 'judge_vote'])->default('public_vote');
            $table->foreignId('judge_id')->nullable()->constrained('users')->nullOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index('nominee_id');
            $table->index('category_id');
            $table->index('voter_id');
            $table->unique(['voter_id', 'nominee_id'], 'unique_voter_nominee_vote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
