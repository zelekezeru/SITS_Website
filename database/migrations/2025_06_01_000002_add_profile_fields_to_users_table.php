<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('email');
            $table->boolean('is_active')->default(false)->after('is_approved');
            $table->boolean('password_changed')->default(false)->after('is_active');
            $table->string('default_password')->nullable()->after('password_changed');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_approved', 'is_active', 'password_changed', 'default_password']);
        });
    }
};
