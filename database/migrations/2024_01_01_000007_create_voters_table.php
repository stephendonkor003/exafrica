<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voters', function (Blueprint $table) {
            $table->id();
            $table->string('mac_address')->unique();
            $table->integer('vote_count')->default(0);
            $table->timestamp('last_voted_at')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->text('block_reason')->nullable();
            $table->timestamps();
            $table->index('mac_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voters');
    }
};
