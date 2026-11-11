<?php

namespace App\Controller;

use App\Service\TournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tournaments')]
class TournamentController extends AbstractController
{
    private const GROUPS = ['groups' => ['tournament:read', 'court:read']];

    public function __construct(private TournamentService $tournamentService)
    {
    }

    #[Route('', name: 'api_tournaments_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->tournamentService->getPublished(), Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('/{id}', name: 'api_tournaments_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->json($this->tournamentService->getPublishedOne($id), Response::HTTP_OK, [], self::GROUPS);
    }
}
