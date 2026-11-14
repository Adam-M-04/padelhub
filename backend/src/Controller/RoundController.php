<?php

namespace App\Controller;

use App\Service\RoundService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

class RoundController extends AbstractController
{
    public function __construct(private RoundService $roundService)
    {
    }

    #[Route('/api/tournaments/{id}/rounds', name: 'api_tournament_rounds', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function list(Uuid $id): JsonResponse
    {
        $groups = ['groups' => ['round:read', 'court:read', 'player:read']];

        return $this->json($this->roundService->getList($id), Response::HTTP_OK, [], $groups);
    }

    #[Route('/api/tournaments/{id}/ranking', name: 'api_tournament_ranking', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function ranking(Uuid $id): JsonResponse
    {
        return $this->json($this->roundService->getRanking($id), Response::HTTP_OK, [], ['groups' => ['player:read']]);
    }
}
