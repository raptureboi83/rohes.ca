<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('template_key')->default('text_block');
            $table->string('anchor_id')->nullable();
            $table->string('eyebrow')->nullable();
            $table->text('intro')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'slug']);
            $table->index('template_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
