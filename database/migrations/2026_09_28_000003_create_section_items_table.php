<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('item_type')->default('default');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('link_url')->nullable();
            $table->string('link_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_items');
    }
};
