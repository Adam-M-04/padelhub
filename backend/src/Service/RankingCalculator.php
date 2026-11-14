<?php

namespace App\Service;

class RankingCalculator
{
    public function calculate(array $matches): array
    {
        $rows = [];

        foreach ($matches as $match) {
            if (!$match->hasScore()) {
                continue;
            }

            $this->addResult($rows, $match->getTeam1(), $match->getTeam1Score(), $match->getTeam2Score());
            $this->addResult($rows, $match->getTeam2(), $match->getTeam2Score(), $match->getTeam1Score());
        }

        usort($rows, fn (array $a, array $b) => [$b['points'], $b['wins']] <=> [$a['points'], $a['wins']]);

        return $rows;
    }

    private function addResult(array &$rows, array $team, int $scored, int $conceded): void
    {
        foreach ($team as $player) {
            $key = (string) $player->getId();
            $rows[$key] ??= ['player' => $player, 'points' => 0, 'matches' => 0, 'wins' => 0];

            $rows[$key]['points'] += $scored;
            $rows[$key]['matches']++;

            if ($scored > $conceded) {
                $rows[$key]['wins']++;
            }
        }
    }
}
