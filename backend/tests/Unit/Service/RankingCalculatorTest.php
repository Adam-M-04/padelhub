<?php

namespace App\Tests\Unit\Service;

use App\Entity\Court;
use App\Entity\GameMatch;
use App\Entity\Round;
use App\Entity\Tournament;
use App\Entity\User;
use App\Service\RankingCalculator;
use PHPUnit\Framework\TestCase;

class RankingCalculatorTest extends TestCase
{
    private array $players = [];

    protected function setUp(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->players[] = new User();
        }
    }

    public function testPointsAreSummedAndSorted(): void
    {
        [$a, $b, $c, $d] = $this->players;
        $matches = [
            $this->match([$a, $b], [$c, $d], 10, 14),
            $this->match([$a, $c], [$b, $d], 20, 4),
        ];

        $ranking = (new RankingCalculator())->calculate($matches);

        $this->assertSame([$c, $a, $d, $b], array_column($ranking, 'player'));
        $this->assertSame([34, 30, 18, 14], array_column($ranking, 'points'));
        $this->assertSame([2, 1, 1, 0], array_column($ranking, 'wins'));
        $this->assertSame([2, 2, 2, 2], array_column($ranking, 'matches'));
    }

    public function testWinsDecideWhenPointsAreEqual(): void
    {
        [$a, $b, $c, $d] = $this->players;
        $matches = [
            $this->match([$a, $b], [$c, $d], 12, 12),
            $this->match([$c, $a], [$d, $b], 13, 11),
            $this->match([$d, $a], [$b, $c], 11, 13),
        ];

        $ranking = (new RankingCalculator())->calculate($matches);

        $this->assertSame([$c, $a, $b, $d], array_column($ranking, 'player'));
        $this->assertSame([38, 36, 36, 34], array_column($ranking, 'points'));
        $this->assertSame([2, 1, 1, 0], array_column($ranking, 'wins'));
    }

    public function testMatchesWithoutScoreAreSkipped(): void
    {
        [$a, $b, $c, $d] = $this->players;
        $match = new GameMatch(new Round(new Tournament(), 1), new Court(), [$a, $b], [$c, $d]);

        $this->assertSame([], (new RankingCalculator())->calculate([$match]));
    }

    private function match(array $team1, array $team2, int $team1Score, int $team2Score): GameMatch
    {
        $match = new GameMatch(new Round(new Tournament(), 1), new Court(), $team1, $team2);
        $match->setScore($team1Score, $team2Score);

        return $match;
    }
}
