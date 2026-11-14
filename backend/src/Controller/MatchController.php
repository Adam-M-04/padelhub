<?php

namespace App\Controller;

use App\Dto\ScoreRequest;
use App\Entity\User;
use App\Service\MatchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

class MatchController extends AbstractController
{
    public function __construct(private MatchService $matchService)
    {
    }

    #[Route('/api/matches/{id}/score', name: 'api_match_score', requirements: ['id' => Requirement::UUID], methods: ['PUT'])]
    #[IsGranted(User::ROLE_PLAYER)]
    public function score(Uuid $id, #[MapRequestPayload] ScoreRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $match = $this->matchService->saveScore($id, $request, $user);
        $groups = ['groups' => ['match:read', 'court:read', 'player:read']];

        return $this->json($match, Response::HTTP_OK, [], $groups);
    }
}
