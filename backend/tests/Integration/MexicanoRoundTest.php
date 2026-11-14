<?php

namespace App\Tests\Integration;

use App\Entity\User;
use App\Enum\TournamentFormat;

class MexicanoRoundTest extends ApiTestCase
{
    public function testNextRoundGroupsPlayersByRanking(): void
    {
        $adminToken = $this->createToken($this->createUser('admin@example.com', [User::ROLE_ADMIN]));
        $id = (string) $this->createOpenTournament(8, TournamentFormat::MEXICANO)->getId();
        for ($i = 1; $i <= 8; $i++) {
            $this->joinTournament($id, $this->createUser('gracz' . $i . '@example.com'));
        }
        $this->jsonRequest('POST', '/api/admin/tournaments/' . $id . '/start', null, $adminToken);

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('UPDATE game_matches SET team1_score = 20, team2_score = 4 ORDER BY id LIMIT 1');
        $connection->executeStatement('UPDATE game_matches SET team1_score = 15, team2_score = 9 WHERE team1_score IS NULL');

        $this->jsonRequest('GET', '/api/tournaments/' . $id . '/rounds');
        [$firstMatch, $secondMatch] = $this->responseData()[0]['matches'];
        $best = array_column($firstMatch['team1'], 'id');
        $second = array_column($secondMatch['team1'], 'id');
        $third = array_column($secondMatch['team2'], 'id');
        $worst = array_column($firstMatch['team2'], 'id');

        $this->jsonRequest('POST', '/api/admin/tournaments/' . $id . '/rounds', null, $adminToken);

        $this->assertResponseStatusCodeSame(201);
        [$topMatch, $bottomMatch] = $this->responseData()['matches'];

        $this->assertSame([$best[0], $second[1]], array_column($topMatch['team1'], 'id'));
        $this->assertSame([$best[1], $second[0]], array_column($topMatch['team2'], 'id'));
        $this->assertSame([$third[0], $worst[1]], array_column($bottomMatch['team1'], 'id'));
        $this->assertSame([$third[1], $worst[0]], array_column($bottomMatch['team2'], 'id'));
    }
}
