<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('interest')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['is_archived', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
