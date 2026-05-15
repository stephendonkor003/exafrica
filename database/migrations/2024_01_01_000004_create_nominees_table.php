<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nominees', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->text('bio')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('profile_image')->nullable();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected', 'published'])->default('pending');
            $table->integer('vote_count')->default(0);
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index('category_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nominees');
    }
};
