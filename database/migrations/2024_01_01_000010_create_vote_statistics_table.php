<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vote_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nominee_id')->constrained('nominees')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->integer('public_votes')->default(0);
            $table->integer('judge_votes')->default(0);
            $table->integer('total_votes')->default(0);
            $table->decimal('percentage', 8, 2)->default(0);
            $table->integer('rank')->nullable();
            $table->timestamps();
            $table->index('nominee_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_statistics');
    }
};
