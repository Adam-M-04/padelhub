<?php

namespace App\Controller;

use App\Entity\Round;
use App\Service\RoundService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

#[OA\Tag(name: 'Admin – turnieje')]
class AdminRoundController extends AbstractController
{
    public function __construct(private RoundService $roundService)
    {
    }

    #[Route('/api/admin/tournaments/{id}/rounds', name: 'api_admin_rounds_create', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[OA\Post(
        summary: 'Wygenerowanie kolejnej rundy',
        description: 'Wszystkie mecze bieżącej rundy muszą mieć wyniki, runda zostaje zamknięta. Americano: pary jak najrzadziej się powtarzają. Mexicano: czwórki według rankingu, pary 1+4 przeciwko 2+3. Gracze dostają e-mail z kortem, partnerem i przeciwnikami.',
    )]
    #[OA\Response(response: 201, description: 'Nowa runda', content: new Model(type: Round::class, groups: ['round:read', 'court:read', 'player:read']))]
    #[OA\Response(response: 401, description: 'UNAUTHORIZED, INVALID_TOKEN, TOKEN_EXPIRED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 403, description: 'FORBIDDEN – wymagana rola ADMIN', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'TOURNAMENT_NOT_IN_PROGRESS, ROUND_NOT_FINISHED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function create(Uuid $id): JsonResponse
    {
        $round = $this->roundService->createNextRound($id);
        $groups = ['groups' => ['round:read', 'court:read', 'player:read']];

        return $this->json($round, Response::HTTP_CREATED, [], $groups);
    }
}
