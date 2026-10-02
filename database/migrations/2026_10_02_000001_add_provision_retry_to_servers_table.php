<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedTinyInteger('provision_attempts')->default(0);
            $table->timestamp('provision_next_at')->nullable();
            $table->text('provision_last_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['provision_attempts', 'provision_next_at', 'provision_last_error']);
        });
    }
};
