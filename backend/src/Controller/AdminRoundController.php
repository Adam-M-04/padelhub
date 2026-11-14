<?php

namespace App\Controller;

use App\Service\RoundService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

class AdminRoundController extends AbstractController
{
    public function __construct(private RoundService $roundService)
    {
    }

    #[Route('/api/admin/tournaments/{id}/rounds', name: 'api_admin_rounds_create', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function create(Uuid $id): JsonResponse
    {
        $round = $this->roundService->createNextRound($id);
        $groups = ['groups' => ['round:read', 'court:read', 'player:read']];

        return $this->json($round, Response::HTTP_CREATED, [], $groups);
    }
}
