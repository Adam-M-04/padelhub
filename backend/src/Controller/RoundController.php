<?php

namespace App\Controller;

use App\Entity\Round;
use App\Entity\User;
use App\Service\RoundService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

#[OA\Tag(name: 'Rozgrywka')]
class RoundController extends AbstractController
{
    public function __construct(private RoundService $roundService)
    {
    }

    #[Route('/api/tournaments/{id}/rounds', name: 'api_tournament_rounds', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'Rundy turnieju z meczami')]
    #[OA\Response(response: 200, description: 'Rundy od pierwszej', content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(ref: new Model(type: Round::class, groups: ['round:read', 'court:read', 'player:read'])),
    ))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[Security(name: null)]
    public function list(Uuid $id): JsonResponse
    {
        $groups = ['groups' => ['round:read', 'court:read', 'player:read']];

        return $this->json($this->roundService->getList($id), Response::HTTP_OK, [], $groups);
    }

    #[Route('/api/tournaments/{id}/ranking', name: 'api_tournament_ranking', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'Ranking turnieju', description: 'Suma punktów ze wszystkich meczów z wynikiem. Przy remisie decyduje liczba wygranych.')]
    #[OA\Response(response: 200, description: 'Ranking', content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
        new OA\Property(property: 'position', type: 'integer', example: 1),
        new OA\Property(property: 'player', ref: new Model(type: User::class, groups: ['player:read'])),
        new OA\Property(property: 'points', type: 'integer', example: 52),
        new OA\Property(property: 'matches', type: 'integer', example: 3),
        new OA\Property(property: 'wins', type: 'integer', example: 2),
    ])))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[Security(name: null)]
    public function ranking(Uuid $id): JsonResponse
    {
        return $this->json($this->roundService->getRanking($id), Response::HTTP_OK, [], ['groups' => ['player:read']]);
    }
}
