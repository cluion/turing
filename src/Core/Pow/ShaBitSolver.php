<?php
declare(strict_types=1);

namespace Cluion\Turing\Core\Pow;

/**
 * Hashcash-style: find a counter so SHA-256(salt . counter) has at least
 * difficulty_bits leading zero bits. GPU/ASIC resistance is low; opt-in mode.
 */
final class ShaBitSolver implements PowSolver
{
    /**
     * Recompute the hash and check its leading-zero-bit count.
     */
    public function verify(array $d, int $counter): bool
    {
        $salt = $d['salt'] ?? '';
        $need = (int) ($d['difficulty_bits'] ?? 0);
        $hash = hash('sha256', $salt . $counter, true);
        return $this->leadingZeroBits($hash) >= $need;
    }

    /**
     * Return the algorithm id this solver handles.
     */
    public function algorithm(): string
    {
        return 'SHA-256';
    }

    /**
     * Count the leading zero bits of a binary string.
     */
    private function leadingZeroBits(string $bin): int
    {
        $bits = 0;
        $len = strlen($bin);
        for ($i = 0; $i < $len; $i++) {
            $v = ord($bin[$i]);
            if ($v === 0) {
                $bits += 8;
                continue;
            }
            for ($b = 7; $b >= 0; $b--) {
                if (($v >> $b) & 1) {
                    return $bits;
                }
                $bits++;
            }
        }
        return $bits;
    }
}
