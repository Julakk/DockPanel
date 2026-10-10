<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * daemon_token sebelumnya disimpan plaintext. Sekarang terenkripsi (cast
 * 'encrypted' di model Node, kunci = APP_KEY) dan dicari lewat sha256-nya
 * di kolom daemon_token_hash. Hasil enkripsi ~300 karakter, jadi kolom
 * dilebarin ke TEXT (varchar(255) bakal motong token).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->text('daemon_token')->nullable()->change();
        });

        if (! Schema::hasColumn('nodes', 'daemon_token_hash')) {
            Schema::table('nodes', function (Blueprint $table) {
                $table->string('daemon_token_hash', 64)->nullable()->index();
            });
        }

        foreach (DB::table('nodes')->whereNotNull('daemon_token')->get() as $node) {
            $raw = (string) $node->daemon_token;

            try {
                // Udah terenkripsi (migrasi pernah jalan sebagian): pakai apa adanya.
                $plain = Crypt::decryptString($raw);
                $encrypted = $raw;
            } catch (Throwable) {
                $plain = $raw;
                $encrypted = Crypt::encryptString($raw);
            }

            DB::table('nodes')->where('id', $node->id)->update([
                'daemon_token' => $encrypted,
                'daemon_token_hash' => hash('sha256', $plain),
            ]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('nodes')->whereNotNull('daemon_token')->get() as $node) {
            try {
                $plain = Crypt::decryptString((string) $node->daemon_token);
            } catch (Throwable) {
                continue;
            }

            DB::table('nodes')->where('id', $node->id)->update(['daemon_token' => $plain]);
        }

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropIndex(['daemon_token_hash']);
            $table->dropColumn('daemon_token_hash');
        });
    }
};
