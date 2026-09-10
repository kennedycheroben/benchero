<?php

namespace Benchero\Core;

/**
 * Benchero ULID generator.
 *
 * Generates a 26-character Universally Unique Lexicographically Sortable Identifier
 * using Crockford Base32 encoding (spec: https://github.com/ulid/spec).
 *
 * Format: TTTTTTTTTT RRRRRRRRRRRRRRRR
 *   - 10 chars: 48-bit Unix millisecond timestamp
 *   - 16 chars: 80-bit cryptographically random component
 *
 * Properties:
 *   - 26 ASCII characters (fits CHAR(26))
 *   - Lexicographically sortable by generation time (within same millisecond,
 *     order is random — monotonicity not required for this application)
 *   - URL-safe, case-insensitive, no special characters
 *   - ~5.48 × 10^18 unique values per millisecond from the random component
 *
 * No external dependencies required. Uses only PHP built-ins: microtime(), random_bytes().
 *
 * Usage:
 *   $id = Ulid::generate();          // "01ARZ3NDEKTSV4RRFFQ69G5FAV"
 *   $ts = Ulid::toTimestamp($id);    // 1734567890123 (Unix ms)
 *   Ulid::isValid($id);              // true
 */
final class Ulid
{
    /**
     * Crockford Base32 alphabet.
     * Excludes I, L, O, U to reduce ambiguity and profanity risk.
     */
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Generate a new ULID string.
     *
     * @throws \Random\RandomException on CSPRNG failure (PHP 8.2+)
     */
    public static function generate(): string
    {
        return self::encodeTimestamp() . self::encodeRandom();
    }

    /**
     * Validate a ULID string.
     * Accepts upper or lower-case; checks length and alphabet.
     */
    public static function isValid(string $ulid): bool
    {
        if (strlen($ulid) !== 26) {
            return false;
        }
        // First char of a ULID must be 0-7 (48-bit timestamp does not overflow)
        $upper = strtoupper($ulid);
        if (strpos('01234567', $upper[0]) === false) {
            return false;
        }
        return preg_match('/^[0123456789ABCDEFGHJKMNPQRSTVWXYZ]{26}$/i', $ulid) === 1;
    }

    /**
     * Decode the embedded Unix millisecond timestamp from a ULID.
     */
    public static function toTimestamp(string $ulid): int
    {
        $chars = strtoupper(substr($ulid, 0, 10));
        $map   = array_flip(str_split(self::ALPHABET));
        $ms    = 0;
        foreach (str_split($chars) as $char) {
            $ms = ($ms << 5) | $map[$char];
        }
        return $ms;
    }

    // -------------------------------------------------------------------------

    /**
     * Encode the current Unix millisecond timestamp into 10 Crockford Base32 chars.
     */
    private static function encodeTimestamp(): string
    {
        $ms     = (int) (microtime(true) * 1000);
        $result = '';
        for ($i = 9; $i >= 0; $i--) {
            $result = self::ALPHABET[$ms & 31] . $result;
            $ms   >>= 5;
        }
        return $result;
    }

    /**
     * Generate 80 bits of cryptographically random data encoded as 16 Crockford
     * Base32 chars.
     *
     * The 80 bits are split into two 40-bit chunks to stay within PHP's native
     * 64-bit signed integer range on all 64-bit platforms, avoiding the need for
     * GMP or BCMath extensions.
     */
    private static function encodeRandom(): string
    {
        $bytes = random_bytes(10); // 80 bits

        // High 40 bits (bytes 0-4)
        $hi = 0;
        for ($i = 0; $i < 5; $i++) {
            $hi = ($hi << 8) | ord($bytes[$i]);
        }

        // Low 40 bits (bytes 5-9)
        $lo = 0;
        for ($i = 5; $i < 10; $i++) {
            $lo = ($lo << 8) | ord($bytes[$i]);
        }

        // 40 bits → 8 Crockford chars each
        $hiStr = '';
        for ($i = 7; $i >= 0; $i--) {
            $hiStr = self::ALPHABET[$hi & 31] . $hiStr;
            $hi  >>= 5;
        }

        $loStr = '';
        for ($i = 7; $i >= 0; $i--) {
            $loStr = self::ALPHABET[$lo & 31] . $loStr;
            $lo  >>= 5;
        }

        return $hiStr . $loStr;
    }
}
