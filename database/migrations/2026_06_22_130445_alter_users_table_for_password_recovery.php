<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // `text` to leave room for the encrypted cast's ciphertext.
            $table->text('default_password')->nullable()->change();
            $table->timestamp('password_reset_requested_at')->nullable()->after('default_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_reset_requested_at');
            $table->string('default_password')->nullable()->change();
        });
    }
};
