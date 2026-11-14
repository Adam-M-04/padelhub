<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Service\TournamentService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

#[Route('/api/tournaments')]
#[OA\Tag(name: 'Turnieje')]
class TournamentController extends AbstractController
{
    private const GROUPS = ['groups' => ['tournament:read', 'court:read']];

    public function __construct(private TournamentService $tournamentService)
    {
    }

    #[Route('', name: 'api_tournaments_list', methods: ['GET'])]
    #[OA\Get(summary: 'Lista opublikowanych turniejów', description: 'Bez szkiców, posortowane po dacie startu.')]
    #[OA\Response(response: 200, description: 'Lista turniejów', content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(ref: new Model(type: Tournament::class, groups: ['tournament:read', 'court:read'])),
    ))]
    #[Security(name: null)]
    public function list(): JsonResponse
    {
        return $this->json($this->tournamentService->getPublished(), Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('/{id}', name: 'api_tournaments_get', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'Szczegóły turnieju')]
    #[OA\Response(response: 200, description: 'Turniej', content: new Model(type: Tournament::class, groups: ['tournament:read', 'court:read']))]
    #[OA\Response(response: 404, description: 'NOT_FOUND – turniej nie istnieje albo jest szkicem', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[Security(name: null)]
    public function get(Uuid $id): JsonResponse
    {
        return $this->json($this->tournamentService->getPublishedOne($id), Response::HTTP_OK, [], self::GROUPS);
    }
}
