<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            // null = tanpa batas, 0 = user nggak boleh bikin database sendiri
            $table->unsignedInteger('database_limit')->nullable()->default(2);
        });

        Schema::table('database_hosts', function (Blueprint $table) {
            // Alamat yang dipakai game server / klien buat konek (kosong = pakai kolom host)
            $table->string('public_host')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('database_hosts', function (Blueprint $table) {
            $table->dropColumn('public_host');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('database_limit');
        });
    }
};
