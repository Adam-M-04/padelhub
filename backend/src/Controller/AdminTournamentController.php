<?php

namespace App\Controller;

use App\Dto\TournamentRequest;
use App\Service\TournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

#[Route('/api/admin/tournaments')]
class AdminTournamentController extends AbstractController
{
    private const GROUPS = ['groups' => ['tournament:read', 'court:read']];

    public function __construct(private TournamentService $tournamentService)
    {
    }

    #[Route('', name: 'api_admin_tournaments_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->tournamentService->getAll(), Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('/{id}', name: 'api_admin_tournaments_get', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function get(Uuid $id): JsonResponse
    {
        return $this->json($this->tournamentService->get($id), Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('', name: 'api_admin_tournaments_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] TournamentRequest $request): JsonResponse
    {
        $tournament = $this->tournamentService->create($request);

        return $this->json($tournament, Response::HTTP_CREATED, [], self::GROUPS);
    }

    #[Route('/{id}', name: 'api_admin_tournaments_update', requirements: ['id' => Requirement::UUID], methods: ['PUT'])]
    public function update(Uuid $id, #[MapRequestPayload] TournamentRequest $request): JsonResponse
    {
        $tournament = $this->tournamentService->update($id, $request);

        return $this->json($tournament, Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('/{id}', name: 'api_admin_tournaments_delete', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    public function delete(Uuid $id): Response
    {
        $this->tournamentService->delete($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/open', name: 'api_admin_tournaments_open', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function open(Uuid $id): JsonResponse
    {
        $tournament = $this->tournamentService->open($id);

        return $this->json($tournament, Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('/{id}/start', name: 'api_admin_tournaments_start', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function start(Uuid $id): JsonResponse
    {
        $tournament = $this->tournamentService->start($id);

        return $this->json($tournament, Response::HTTP_OK, [], self::GROUPS);
    }
}
