<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('source'); // path di host node
            $table->string('target'); // path di dalam container
            $table->boolean('read_only')->default(false);
            $table->timestamps();
        });

        // Pivot: mount ini aktif di node mana aja
        Schema::create('mount_node', function (Blueprint $table) {
            $table->foreignId('mount_id')->constrained('mounts')->cascadeOnDelete();
            $table->foreignId('node_id')->constrained('nodes')->cascadeOnDelete();
            $table->primary(['mount_id', 'node_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mount_node');
        Schema::dropIfExists('mounts');
    }
};
