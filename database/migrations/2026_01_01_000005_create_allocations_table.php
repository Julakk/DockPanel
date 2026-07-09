<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('nodes')->cascadeOnDelete();
            $table->string('ip');
            $table->string('ip_alias')->nullable();
            $table->unsignedInteger('port');
            $table->foreignId('server_id')->nullable()->constrained('servers')->nullOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['node_id', 'ip', 'port']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
