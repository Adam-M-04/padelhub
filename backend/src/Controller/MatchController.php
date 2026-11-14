<?php

namespace App\Controller;

use App\Dto\ScoreRequest;
use App\Entity\GameMatch;
use App\Entity\User;
use App\Service\MatchService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[OA\Tag(name: 'Rozgrywka')]
class MatchController extends AbstractController
{
    public function __construct(private MatchService $matchService)
    {
    }

    #[Route('/api/matches/{id}/score', name: 'api_match_score', requirements: ['id' => Requirement::UUID], methods: ['PUT'])]
    #[IsGranted(User::ROLE_PLAYER)]
    #[OA\Put(
        summary: 'Wpisanie wyniku meczu',
        description: 'Wynik wpisuje gracz meczu albo admin, po zamknięciu rundy tylko admin. Suma punktów musi być równa puli turnieju. Pole version to wersja meczu z odczytu – gdy ktoś zmienił wynik w międzyczasie, odpowiedź to 409.',
    )]
    #[OA\Response(response: 200, description: 'Mecz z zapisanym wynikiem i nową wersją', content: new Model(type: GameMatch::class, groups: ['match:read', 'court:read', 'player:read']))]
    #[OA\Response(response: 400, description: 'VALIDATION_ERROR – brak pól albo zła suma punktów (pole score)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 401, description: 'UNAUTHORIZED, INVALID_TOKEN, TOKEN_EXPIRED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 403, description: 'FORBIDDEN – użytkownik nie gra w tym meczu', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'MATCH_VERSION_CONFLICT, ROUND_CLOSED, TOURNAMENT_NOT_IN_PROGRESS', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function score(Uuid $id, #[MapRequestPayload] ScoreRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $match = $this->matchService->saveScore($id, $request, $user);
        $groups = ['groups' => ['match:read', 'court:read', 'player:read']];

        return $this->json($match, Response::HTTP_OK, [], $groups);
    }
}
