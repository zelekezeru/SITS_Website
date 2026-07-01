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
        Schema::create('staging_books', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->index();
            $table->string('source_file')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->json('raw');
            $table->json('cleaned')->nullable();
            $table->enum('status', ['pending', 'cleaned', 'imported', 'rejected', 'duplicate'])->default('pending')->index();
            $table->text('errors')->nullable();
            $table->foreignId('book_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['legacy_id', 'source_file']);
        });

        Schema::create('staging_copies', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->index();
            $table->string('legacy_book_id')->index();
            $table->string('source_file')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->json('raw');
            $table->json('cleaned')->nullable();
            $table->enum('status', ['pending', 'cleaned', 'imported', 'rejected', 'duplicate'])->default('pending')->index();
            $table->text('errors')->nullable();
            $table->foreignId('book_copy_id')->nullable()->constrained('book_copies')->nullOnDelete();
            $table->timestamps();
            $table->unique(['legacy_id', 'source_file']);
        });

        Schema::create('staging_users', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->index();
            $table->string('source_file')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->json('raw');
            $table->json('cleaned')->nullable();
            $table->enum('status', ['pending', 'cleaned', 'imported', 'rejected', 'duplicate'])->default('pending')->index();
            $table->text('errors')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['legacy_id', 'source_file']);
        });

        Schema::create('staging_loans', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->index();
            $table->string('source_file')->nullable();
            $table->json('raw');
            $table->enum('status', ['pending', 'imported', 'rejected'])->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staging_loans');
        Schema::dropIfExists('staging_users');
        Schema::dropIfExists('staging_copies');
        Schema::dropIfExists('staging_books');
    }
};
