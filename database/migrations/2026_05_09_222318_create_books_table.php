<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('isbn', 20)->nullable()->index();
            $table->string('publisher')->nullable();
            $table->year('published_year')->nullable();
            $table->string('edition')->nullable();
            $table->string('language', 10)->default('en');
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
