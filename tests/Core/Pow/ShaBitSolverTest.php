<?php
declare(strict_types=1);

namespace Cluion\Turing\Tests\Core\Pow;

use Cluion\Turing\Core\Pow\ShaBitSolver;
use PHPUnit\Framework\TestCase;

final class ShaBitSolverTest extends TestCase
{
    /**
     * A counter meeting the difficulty verifies; the same counter fails an
     * unreachably high difficulty (deterministic, non-flaky).
     */
    public function test_verify_accepts_counter_meeting_difficulty(): void
    {
        $solver = new ShaBitSolver();
        $salt = 'salt-x';

        // Brute-force a counter whose hash has >= 4 leading zero bits.
        $counter = 0;
        while ($this->leadingZeroBits(hash('sha256', $salt . $counter, true)) < 4) {
            $counter++;
            if ($counter > 100000) {
                self::fail('could not find a counter in test');
            }
        }

        self::assertTrue($solver->verify(['salt' => $salt, 'difficulty_bits' => 4], $counter));
        // 32 leading zero bits is astronomically unlikely for this same counter.
        self::assertFalse($solver->verify(['salt' => $salt, 'difficulty_bits' => 32], $counter));
    }

    /**
     * Count leading zero bits of a binary string (test-local reference impl).
     */
    private function leadingZeroBits(string $bin): int
    {
        $bits = 0;
        foreach (str_split($bin) as $byte) {
            $v = ord($byte);
            if ($v === 0) {
                $bits += 8;
                continue;
            }
            for ($i = 7; $i >= 0; $i--) {
                if (($v >> $i) & 1) {
                    return $bits;
                }
                $bits++;
            }
        }
        return $bits;
    }
}
