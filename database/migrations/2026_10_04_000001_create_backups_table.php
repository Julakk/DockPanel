<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('name');
            $table->string('status', 20)->default('creating');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 128)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedInteger('backup_limit')->default(3);
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('backup_limit');
        });

        Schema::dropIfExists('backups');
    }
};
