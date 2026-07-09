<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eggs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nest_id')->constrained('nests')->cascadeOnDelete();
            $table->string('uuid')->unique();
            $table->string('name'); // ex: "Vanilla SA-MP Server"
            $table->text('description')->nullable();

            $table->string('docker_image'); // image default
            $table->json('docker_images')->nullable(); // opsi multi-image (mis. versi PHP/Java beda)

            $table->text('startup'); // startup command template, pakai {{VAR}} placeholder
            $table->json('config_files')->nullable(); // file yang di-parse Wings (server.properties, dll)
            $table->json('config_startup')->nullable(); // regex "done" detection buat status server
            $table->json('config_stop')->nullable(); // command/signal buat stop server

            $table->string('script_container')->default('alpine:3.4');
            $table->text('script_install')->nullable(); // install script dijalanin sekali pas provisioning

            $table->timestamps();
        });

        // Variabel environment yang bisa diisi user per-server, mengacu ke egg ini
        Schema::create('egg_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('egg_id')->constrained('eggs')->cascadeOnDelete();
            $table->string('name'); // label tampil di UI, ex: "Server Jar File"
            $table->string('env_variable'); // ex: SERVER_JARFILE
            $table->text('description')->nullable();
            $table->string('default_value')->nullable();
            $table->boolean('user_viewable')->default(true);
            $table->boolean('user_editable')->default(true);
            $table->string('rules')->default('nullable|string'); // laravel validation rule
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_variables');
        Schema::dropIfExists('eggs');
    }
};
