<?php

namespace App\Controller;

use App\Dto\TournamentRequest;
use App\Entity\Tournament;
use App\Service\TournamentService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

#[Route('/api/admin/tournaments')]
#[OA\Tag(name: 'Admin – turnieje')]
#[OA\Response(response: 401, description: 'UNAUTHORIZED, INVALID_TOKEN, TOKEN_EXPIRED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 403, description: 'FORBIDDEN – wymagana rola ADMIN', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
class AdminTournamentController extends AbstractController
{
    private const GROUPS = ['groups' => ['tournament:read', 'court:read']];

    public function __construct(private TournamentService $tournamentService)
    {
    }

    #[Route('', name: 'api_admin_tournaments_list', methods: ['GET'])]
    #[OA\Get(summary: 'Wszystkie turnieje, także szkice')]
    #[OA\Response(response: 200, description: 'Lista turniejów', content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(ref: new Model(type: Tournament::class, groups: ['tournament:read', 'court:read'])),
    ))]
    public function list(): JsonResponse
    {
        return $this->json($this->tournamentService->getAll(), Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('/{id}', name: 'api_admin_tournaments_get', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'Szczegóły turnieju')]
    #[OA\Response(response: 200, description: 'Turniej', content: new Model(type: Tournament::class, groups: ['tournament:read', 'court:read']))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function get(Uuid $id): JsonResponse
    {
        return $this->json($this->tournamentService->get($id), Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('', name: 'api_admin_tournaments_create', methods: ['POST'])]
    #[OA\Post(summary: 'Utworzenie turnieju (status DRAFT)', description: 'Maksymalna liczba graczy to 4 × liczba kortów. Korty muszą być aktywne.')]
    #[OA\Response(response: 201, description: 'Utworzony turniej', content: new Model(type: Tournament::class, groups: ['tournament:read', 'court:read']))]
    #[OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'COURT_INACTIVE – wybrano nieaktywny kort', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function create(#[MapRequestPayload] TournamentRequest $request): JsonResponse
    {
        $tournament = $this->tournamentService->create($request);

        return $this->json($tournament, Response::HTTP_CREATED, [], self::GROUPS);
    }

    #[Route('/{id}', name: 'api_admin_tournaments_update', requirements: ['id' => Requirement::UUID], methods: ['PUT'])]
    #[OA\Put(summary: 'Edycja turnieju przed startem', description: 'Limitu graczy nie można zmniejszyć poniżej liczby zapisanych. Po zwiększeniu limitu gracze z listy rezerwowej wchodzą na wolne miejsca.')]
    #[OA\Response(response: 200, description: 'Zmieniony turniej', content: new Model(type: Tournament::class, groups: ['tournament:read', 'court:read']))]
    #[OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'TOURNAMENT_NOT_EDITABLE, COURT_INACTIVE', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function update(Uuid $id, #[MapRequestPayload] TournamentRequest $request): JsonResponse
    {
        $tournament = $this->tournamentService->update($id, $request);

        return $this->json($tournament, Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('/{id}', name: 'api_admin_tournaments_delete', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Usunięcie szkicu turnieju')]
    #[OA\Response(response: 204, description: 'Turniej usunięty')]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'TOURNAMENT_NOT_DRAFT – można usunąć tylko szkic', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function delete(Uuid $id): Response
    {
        $this->tournamentService->delete($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/open', name: 'api_admin_tournaments_open', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[OA\Post(summary: 'Otwarcie zapisów (DRAFT → OPEN)')]
    #[OA\Response(response: 200, description: 'Turniej z otwartymi zapisami', content: new Model(type: Tournament::class, groups: ['tournament:read', 'court:read']))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'TOURNAMENT_NOT_DRAFT, COURT_INACTIVE', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function open(Uuid $id): JsonResponse
    {
        $tournament = $this->tournamentService->open($id);

        return $this->json($tournament, Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('/{id}/start', name: 'api_admin_tournaments_start', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[OA\Post(summary: 'Start turnieju (OPEN → IN_PROGRESS)', description: 'Liczba zapisanych graczy musi być podzielna przez 4. Zapisy zostają zamknięte, a pierwsza runda jest losowana.')]
    #[OA\Response(response: 200, description: 'Rozpoczęty turniej', content: new Model(type: Tournament::class, groups: ['tournament:read', 'court:read']))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'TOURNAMENT_NOT_OPEN, INVALID_PLAYER_COUNT, COURT_INACTIVE', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function start(Uuid $id): JsonResponse
    {
        $tournament = $this->tournamentService->start($id);

        return $this->json($tournament, Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('/{id}/finish', name: 'api_admin_tournaments_finish', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[OA\Post(summary: 'Zakończenie turnieju (IN_PROGRESS → FINISHED)', description: 'Wszystkie mecze bieżącej rundy muszą mieć wyniki. Po zakończeniu wyników nie można już zmieniać.')]
    #[OA\Response(response: 200, description: 'Zakończony turniej', content: new Model(type: Tournament::class, groups: ['tournament:read', 'court:read']))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'TOURNAMENT_NOT_IN_PROGRESS, ROUND_NOT_FINISHED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function finish(Uuid $id): JsonResponse
    {
        $tournament = $this->tournamentService->finish($id);

        return $this->json($tournament, Response::HTTP_OK, [], self::GROUPS);
    }
}
