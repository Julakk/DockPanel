<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panel_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('DockPanel');
            $table->enum('require_2fa', ['not_required', 'admin_only', 'all_users'])->default('not_required');
            $table->string('default_language', 5)->default('id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_settings');
    }
};
