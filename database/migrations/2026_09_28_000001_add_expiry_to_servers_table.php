<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('expiry_notified_at')->nullable();
            $table->string('suspension_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'expiry_notified_at', 'suspension_reason']);
        });
    }
};
