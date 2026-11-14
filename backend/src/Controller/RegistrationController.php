<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/tournaments/{id}', requirements: ['id' => Requirement::UUID])]
class RegistrationController extends AbstractController
{
    private const GROUPS = ['groups' => ['registration:read', 'player:read']];

    public function __construct(private RegistrationService $registrationService)
    {
    }

    #[Route('/registrations', name: 'api_tournament_registrations', methods: ['GET'])]
    public function list(Uuid $id): JsonResponse
    {
        return $this->json($this->registrationService->getList($id), Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('/registration', name: 'api_tournament_register', methods: ['POST'])]
    #[IsGranted(User::ROLE_PLAYER)]
    public function register(Uuid $id, #[CurrentUser] User $user): JsonResponse
    {
        $registration = $this->registrationService->register($id, $user);

        return $this->json($registration, Response::HTTP_CREATED, [], self::GROUPS);
    }

    #[Route('/registration', name: 'api_tournament_withdraw', methods: ['DELETE'])]
    #[IsGranted(User::ROLE_PLAYER)]
    public function withdraw(Uuid $id, #[CurrentUser] User $user): Response
    {
        $this->registrationService->withdraw($id, $user);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
