<?php
/**
 * BoviLogic – TOTP (RFC 6238) pure-PHP implementation
 * Compatible with Google Authenticator, Authy, Microsoft Authenticator, etc.
 */
class TOTP {

    private const CHARS  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;

    /** Generate a cryptographically random Base32 secret */
    public static function generateSecret(int $bytes = 20): string {
        $raw     = random_bytes($bytes);
        $secret  = '';
        $buffer  = 0;
        $bitsLeft = 0;
        foreach (str_split($raw) as $byte) {
            $buffer    = ($buffer << 8) | ord($byte);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $secret .= self::CHARS[($buffer >> $bitsLeft) & 31];
            }
        }
        return $secret;
    }

    /** Decode a Base32 string to raw bytes */
    private static function base32Decode(string $input): string {
        $input    = strtoupper(preg_replace('/\s+/', '', $input));
        $output   = '';
        $buffer   = 0;
        $bitsLeft = 0;
        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $pos = strpos(self::CHARS, $input[$i]);
            if ($pos === false) continue;
            $buffer    = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $output;
    }

    /** Calculate the 6-digit TOTP code for a given timestamp */
    public static function getCode(string $secret, ?int $timestamp = null, int $period = self::PERIOD): string {
        $t    = (int) floor(($timestamp ?? time()) / $period);
        $key  = self::base32Decode($secret);
        // 8-byte big-endian counter
        $msg  = pack('N*', 0) . pack('N*', $t);
        $hmac = hash_hmac('sha1', $msg, $key, true);
        $off  = ord($hmac[19]) & 0x0F;
        $code = (
            ((ord($hmac[$off])   & 0x7F) << 24) |
            ((ord($hmac[$off+1]) & 0xFF) << 16) |
            ((ord($hmac[$off+2]) & 0xFF) <<  8) |
            ( ord($hmac[$off+3]) & 0xFF)
        ) % (10 ** self::DIGITS);
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a 6-digit code.
     * $window = number of 30s periods to check on each side (handles clock drift).
     */
    public static function verify(string $secret, string $code, int $window = 1): bool {
        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) return false;
        $now = time();
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::getCode($secret, $now + $i * self::PERIOD), $code)) {
                return true;
            }
        }
        return false;
    }

    /** Build otpauth:// URI for QR code */
    public static function getUri(string $secret, string $account, string $issuer = 'BoviLogic'): string {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($account),
            $secret,
            rawurlencode($issuer)
        );
    }
}
