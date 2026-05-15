<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nominee_id')->constrained('nominees')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('nominated_by')->constrained('users')->onDelete('cascade');
            $table->text('nomination_reason');
            $table->text('evaluator_notes')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('evaluation_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
            $table->index('nominee_id');
            $table->index('category_id');
            $table->index('evaluation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nominations');
    }
};
