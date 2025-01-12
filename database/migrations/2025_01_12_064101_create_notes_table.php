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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('uuid')->unique();
            $table->string('title')->nullable(false);
            $table->longText('content');
            $table->boolean('pinned')->default(false);
            $table->boolean('starred')->default(false);
            $table->timestamp('created_at')->nullable(false)->useCurrent();
            $table->timestamp('updated_at')->nullable(false)->useCurrentOnUpdate();
            $table->fullText(['title', 'content']);
            $table->foreign('user_id') // Add a full-text index
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade'); // Add cascade on delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
