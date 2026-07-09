<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('uuid_short', 8)->unique(); // dipakai buat nama docker container

            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('suspended')->default(false);

            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('node_id')->constrained('nodes')->restrictOnDelete();
            $table->foreignId('nest_id')->constrained('nests');
            $table->foreignId('egg_id')->constrained('eggs');

            // resource limit
            $table->integer('memory'); // MB, 0 = unlimited
            $table->integer('swap')->default(0);
            $table->integer('disk'); // MB
            $table->integer('io')->default(500);
            $table->float('cpu')->default(0); // persen, 0 = unlimited
            $table->unsignedTinyInteger('threads')->nullable(); // pin ke core tertentu, nullable

            $table->text('startup'); // startup command final (sudah di-render dari egg template)
            $table->string('image'); // docker image dipakai
            $table->boolean('skip_scripts')->default(false);

            $table->enum('status', ['installing', 'install_failed', 'suspended', 'restoring_backup', 'running', 'stopped', 'offline'])
                ->default('installing');

            $table->timestamps();
        });

        // Nilai variable egg yang di-set spesifik per server (mis. SERVER_JARFILE = paper-1.20.jar)
        Schema::create('server_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->foreignId('egg_variable_id')->constrained('egg_variables')->cascadeOnDelete();
            $table->text('variable_value')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'egg_variable_id']);
        });

        // Subuser: user lain yang dikasih akses terbatas ke satu server
        Schema::create('server_subusers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('permissions'); // ex: ["control.start", "files.read", "console.access"]
            $table->timestamps();

            $table->unique(['server_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_subusers');
        Schema::dropIfExists('server_variables');
        Schema::dropIfExists('servers');
    }
};
