<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->foreignId('database_host_id')->constrained('database_hosts')->cascadeOnDelete();
            $table->string('database');
            $table->string('username');
            $table->text('password'); // di-encrypt lewat Eloquent cast
            $table->timestamps();

            $table->unique(['database_host_id', 'database']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_databases');
    }
};
