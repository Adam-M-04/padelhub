<?php

namespace App\Tests\Unit\Service;

use App\Entity\Court;
use App\Entity\GameMatch;
use App\Entity\Round;
use App\Entity\Tournament;
use App\Entity\User;
use App\Service\AmericanoPairing;
use PHPUnit\Framework\TestCase;

class AmericanoPairingTest extends TestCase
{
    public function testEveryPlayerIsUsedOnce(): void
    {
        $players = $this->createPlayers(12);

        $arranged = (new AmericanoPairing())->arrange($players, []);

        $this->assertCount(12, $arranged);
        $this->assertSame($this->ids($players), $this->ids($arranged));
    }

    public function testPartnersDoNotRepeatInFirstRounds(): void
    {
        $players = $this->createPlayers(8);
        $pairing = new AmericanoPairing();
        $matches = [];

        for ($round = 1; $round <= 5; $round++) {
            $arranged = $pairing->arrange($players, $matches);
            $matches = [...$matches, ...$this->createMatches($arranged)];
        }

        $this->assertSame([1], array_unique(array_values($this->partnerCounts($matches))));
    }

    public function testFourPlayersGetNewPartnerUntilAllCombinationsAreUsed(): void
    {
        $players = $this->createPlayers(4);
        $pairing = new AmericanoPairing();
        $matches = [];

        for ($round = 1; $round <= 3; $round++) {
            $matches = [...$matches, ...$this->createMatches($pairing->arrange($players, $matches))];
        }

        $this->assertCount(6, $this->partnerCounts($matches));
    }

    private function createPlayers(int $count): array
    {
        $players = [];
        for ($i = 0; $i < $count; $i++) {
            $players[] = new User();
        }

        return $players;
    }

    private function createMatches(array $arranged): array
    {
        $round = new Round(new Tournament(), 1);
        $matches = [];

        foreach (array_chunk($arranged, 4) as $group) {
            $matches[] = new GameMatch($round, new Court(), [$group[0], $group[1]], [$group[2], $group[3]]);
        }

        return $matches;
    }

    private function partnerCounts(array $matches): array
    {
        $counts = [];
        foreach ($matches as $match) {
            foreach ([$match->getTeam1(), $match->getTeam2()] as $team) {
                $key = implode('|', $this->ids($team));
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        return $counts;
    }

    private function ids(array $players): array
    {
        $ids = array_map(fn (User $player) => (string) $player->getId(), $players);
        sort($ids);

        return $ids;
    }
}
