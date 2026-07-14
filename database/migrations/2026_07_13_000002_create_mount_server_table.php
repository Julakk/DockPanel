<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mount_server', function (Blueprint $table) {
            $table->foreignId('mount_id')->constrained('mounts')->cascadeOnDelete();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->primary(['mount_id', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mount_server');
    }
};
