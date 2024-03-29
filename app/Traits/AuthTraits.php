<?php
namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait AuthTraits
{
    public int $expirationLimit = 120; // 2 hours
    public int $resendInterval = 1; // 1 minutes

    public function generateRandomToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function getResetTokenByEmail(string $email, ?string $token_key = null): ?object
    {
        $token = DB::table('password_resets')
            ->where('email', $email)
            ->when($token_key, function ($query, $token_key) {
                return $query->where('token', $token_key);
            })
            ->latest()
            ->first();

        return $token;
    }

    public function createResetToken(string $email, string $token): bool
    {
        return DB::table('password_resets')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => now()
        ]);
    }

    public function deleteResetToken(object $token): bool
    {
        if (!$token) {
            return false;
        }

        return DB::table('password_resets')
            ->where('email', $token->email)
            ->where('token', $token->token)
            ->delete();
    }

    public function checkTokenExpiry(object $token): bool
    {
        if (!$token) {
            return true;
        }

        $created_at = strtotime($token->created_at);
        $now = strtotime(now());

        $differenceInSeconds = $now - $created_at;
        $differenceInMinutes = floor($differenceInSeconds / 60);

        return $differenceInMinutes > $this->expirationLimit;
    }

    public function checkResendInterval(object $token): array
    {
        if (!$token) {
            return [
                "reject" => false,
                "time_left" => 0
            ];
        }

        $created_at = strtotime($token->created_at);
        $now = strtotime(now());

        $differenceInSeconds = $now - $created_at;
        $differenceInMinutes = floor($differenceInSeconds / 60);

        $reject = $differenceInMinutes < $this->resendInterval;

        $resendIntervalInSeconds = $this->resendInterval * 60;

        return [
            "reject" => $reject,
            "time_left" => $reject ? ($resendIntervalInSeconds - $differenceInSeconds) : 0,
        ];
    }
}
