<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hash recovery code (terenkripsi), sekali pakai.
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_enabled_at');
            // Time-step TOTP terakhir yang dipakai, buat nolak replay kode yang sama.
            $table->unsignedBigInteger('two_factor_last_step')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_recovery_codes', 'two_factor_last_step']);
        });
    }
};
