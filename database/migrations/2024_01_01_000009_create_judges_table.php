<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('background')->nullable();
            $table->string('profile_image')->nullable();
            $table->boolean('is_published')->default(false);
            $table->integer('vote_count')->default(0);
            $table->text('specialization')->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judges');
    }
};
