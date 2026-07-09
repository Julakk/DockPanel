<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('public')->default(true);
            $table->string('fqdn'); // domain/IP node
            $table->string('scheme')->default('https'); // http/https ke Wings
            $table->boolean('behind_proxy')->default(false);
            $table->boolean('maintenance_mode')->default(false);

            $table->integer('memory'); // total RAM (MB) yang bisa dialokasikan
            $table->integer('memory_overallocate')->default(0);
            $table->integer('disk'); // total disk (MB)
            $table->integer('disk_overallocate')->default(0);

            $table->integer('daemon_listen')->default(8080); // port Wings API
            $table->integer('daemon_sftp')->default(2022);
            $table->string('daemon_token')->nullable(); // token auth Panel -> Wings, di-hash

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
