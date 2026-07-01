<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('call_number')->nullable()->after('isbn');
            $table->string('classification_type')->nullable()->after('call_number')->comment('dewey or loc');
            $table->unsignedInteger('page_count')->nullable()->after('edition');
            $table->string('subject')->nullable()->after('language');
            $table->string('cover_url')->nullable()->after('cover_path');
            $table->text('notes')->nullable()->after('description');

            $table->index('call_number');
            $table->index('subject');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['call_number']);
            $table->dropIndex(['subject']);
            $table->dropColumn(['call_number', 'classification_type', 'page_count', 'subject', 'cover_url', 'notes']);
        });
    }
};
