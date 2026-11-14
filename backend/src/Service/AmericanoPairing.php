<?php

namespace App\Service;

class AmericanoPairing
{
    private const ATTEMPTS = 200;

    public function arrange(array $players, array $previousMatches): array
    {
        $partnerCounts = $this->countPartners($previousMatches);
        $bestPairs = [];
        $bestScore = PHP_INT_MAX;

        for ($i = 0; $i < self::ATTEMPTS && $bestScore > 0; $i++) {
            shuffle($players);
            [$pairs, $score] = $this->pairPlayers($players, $partnerCounts);

            if ($score < $bestScore) {
                $bestPairs = $pairs;
                $bestScore = $score;
            }
        }

        return array_merge(...$bestPairs);
    }

    private function pairPlayers(array $players, array $partnerCounts): array
    {
        $pairs = [];
        $score = 0;

        while (count($players) > 0) {
            $player = array_shift($players);
            $bestIndex = 0;

            foreach ($players as $index => $candidate) {
                if ($this->timesTogether($partnerCounts, $player, $candidate) < $this->timesTogether($partnerCounts, $player, $players[$bestIndex])) {
                    $bestIndex = $index;
                }
            }

            $partner = $players[$bestIndex];
            array_splice($players, $bestIndex, 1);

            $score += $this->timesTogether($partnerCounts, $player, $partner);
            $pairs[] = [$player, $partner];
        }

        return [$pairs, $score];
    }

    private function countPartners(array $matches): array
    {
        $counts = [];

        foreach ($matches as $match) {
            foreach ([$match->getTeam1(), $match->getTeam2()] as [$first, $second]) {
                $key = $this->pairKey($first, $second);
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        return $counts;
    }

    private function timesTogether(array $partnerCounts, $first, $second): int
    {
        return $partnerCounts[$this->pairKey($first, $second)] ?? 0;
    }

    private function pairKey($first, $second): string
    {
        $ids = [(string) $first->getId(), (string) $second->getId()];
        sort($ids);

        return implode('|', $ids);
    }
}
