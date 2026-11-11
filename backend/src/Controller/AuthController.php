<?php

namespace App\Controller;

use App\Dto\RegisterRequest;
use App\Entity\User;
use App\Exception\ApiException;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(private UserService $userService)
    {
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $request): JsonResponse
    {
        $user = $this->userService->register($request);

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user:read']);
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        throw new ApiException('Request body must be JSON with email and password', 400, 'BAD_REQUEST');
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    #[IsGranted(User::ROLE_PLAYER)]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }
}
