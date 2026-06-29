<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->string('title_en');
            $table->string('title_am')->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('job_description_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_description_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_no')->default(1);
            $table->longText('body')->nullable();          // duties, authority, reporting line
            $table->date('effective_from')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Resolve the circular pointer once both tables exist
        Schema::table('job_descriptions', function (Blueprint $table) {
            $table->foreign('current_version_id')
                  ->references('id')->on('job_description_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_descriptions', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('job_description_versions');
        Schema::dropIfExists('job_descriptions');
    }
};
