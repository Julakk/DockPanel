<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * TwoFactorService — implementasi TOTP (Time-based One-Time Password, RFC 6238)
 * pakai HMAC-SHA1, kompatibel sama Google Authenticator / Authy / dsb.
 *
 * Ditulis manual pakai fungsi PHP bawaan (hash_hmac) tanpa dependency composer
 * tambahan, biar nggak nambah risiko compile/install di lingkungan Termux.
 */
class TwoFactorService
{
    private const SECRET_LENGTH = 20; // 160 bit, standar buat TOTP

    private const PERIOD = 30; // detik per time-step

    private const DIGITS = 6;

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate secret baru, di-encode Base32 (format yang dipahami authenticator app).
     */
    public function generateSecret(): string
    {
        $bytes = random_bytes(self::SECRET_LENGTH);

        return $this->base32Encode($bytes);
    }

    /**
     * URI otpauth:// standar, bisa di-paste manual atau di-generate jadi QR
     * pakai tool eksternal kalau perlu. Kita nggak generate QR image di sini
     * biar nggak nambah dependency.
     */
    public function getOtpAuthUri(string $secret, string $email, string $issuer = 'DockPanel'): string
    {
        $label = rawurlencode("{$issuer}:{$email}");
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$params}";
    }

    /**
     * Cari time-step yang cocok sama kode 6 digit dari user (toleransi ±1 step =
     * 30 detik sebelum/sesudah buat jaga-jaga clock drift). null kalau nggak cocok.
     */
    public function matchedStep(string $secret, string $code): ?int
    {
        $code = preg_replace('/\s+/', '', $code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $currentStep = (int) floor(time() / self::PERIOD);

        for ($i = -1; $i <= 1; $i++) {
            if (hash_equals($this->generateCode($secret, $currentStep + $i), $code)) {
                return $currentStep + $i;
            }
        }

        return null;
    }

    /**
     * Verifikasi kode tanpa nyatet pemakaiannya (nggak aman dari replay).
     * Buat login dan aktivasi pakai consume().
     */
    public function verify(string $secret, string $code): bool
    {
        return $this->matchedStep($secret, $code) !== null;
    }

    /**
     * Verifikasi kode DAN tandain time-step-nya udah dipakai, supaya kode yang
     * sama nggak bisa dipakai dua kali. UPDATE-nya atomik: kalau dua request
     * bawa kode yang sama barengan, cuma satu yang kena.
     */
    public function consume(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $step = $this->matchedStep($user->two_factor_secret, $code);

        if ($step === null) {
            return false;
        }

        $updated = User::query()
            ->whereKey($user->getKey())
            ->where(function ($query) use ($step) {
                $query->whereNull('two_factor_last_step')
                    ->orWhere('two_factor_last_step', '<', $step);
            })
            ->update(['two_factor_last_step' => $step]);

        return $updated === 1;
    }

    /**
     * Recovery code berformat xxxxx-xxxxx, tanpa karakter yang gampang ketuker
     * (i, l, o, 0, 1). Yang dikembalikan versi plaintext buat ditampilin sekali;
     * yang disimpan di DB cuma hash-nya (hashRecoveryCode).
     *
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $raw = '';

            for ($j = 0; $j < 10; $j++) {
                $raw .= $alphabet[random_int(0, $max)];
            }

            $codes[] = substr($raw, 0, 5).'-'.substr($raw, 5);
        }

        return $codes;
    }

    public function hashRecoveryCode(string $code): string
    {
        return hash('sha256', strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $code)));
    }

    /**
     * Pakai satu recovery code. Sekali pakai: hash-nya dibuang dari daftar.
     * Barisnya dikunci (FOR UPDATE) biar dua request barengan nggak bisa
     * sama-sama makai kode yang sama.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $hash = $this->hashRecoveryCode($code);

        return DB::transaction(function () use ($user, $hash) {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->first();
            $hashes = $locked?->two_factor_recovery_codes ?? [];

            foreach ($hashes as $index => $stored) {
                if (hash_equals($stored, $hash)) {
                    unset($hashes[$index]);
                    $locked->forceFill(['two_factor_recovery_codes' => array_values($hashes)])->save();

                    return true;
                }
            }

            return false;
        });
    }

    protected function generateCode(string $secret, int $timeStep): string
    {
        $key = $this->base32Decode($secret);
        $data = pack('N*', 0).pack('N*', $timeStep);

        $hash = hash_hmac('sha1', $data, $key, true);

        $offset = ord($hash[19]) & 0x0F;

        $truncated = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $code = $truncated % (10 ** self::DIGITS);

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    protected function base32Encode(string $data): string
    {
        $binaryString = '';
        foreach (str_split($data) as $char) {
            $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($binaryString, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $output .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $output;
    }

    protected function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $binaryString = '';

        foreach (str_split($secret) as $char) {
            $pos = strpos(self::BASE32_ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($binaryString, 8) as $byte) {
            if (strlen($byte) < 8) {
                continue;
            }
            $output .= chr(bindec($byte));
        }

        return $output;
    }
}
