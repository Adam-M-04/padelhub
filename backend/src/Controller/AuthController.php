<?php

namespace App\Controller;

use App\Dto\RegisterRequest;
use App\Entity\User;
use App\Exception\ApiException;
use App\Service\UserService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth')]
#[OA\Tag(name: 'Konto')]
class AuthController extends AbstractController
{
    public function __construct(private UserService $userService)
    {
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    #[OA\Post(summary: 'Rejestracja gracza')]
    #[OA\Response(response: 201, description: 'Utworzone konto', content: new Model(type: User::class, groups: ['user:read']))]
    #[OA\Response(response: 400, description: 'VALIDATION_ERROR, INVALID_JSON', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'EMAIL_TAKEN – e-mail jest już zajęty', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[Security(name: null)]
    public function register(#[MapRequestPayload] RegisterRequest $request): JsonResponse
    {
        $user = $this->userService->register($request);

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user:read']);
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    #[OA\Post(summary: 'Logowanie, zwraca token JWT')]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['email', 'password'],
        properties: [
            new OA\Property(property: 'email', type: 'string', example: 'gracz1@padelhub.local'),
            new OA\Property(property: 'password', type: 'string'),
        ],
    ))]
    #[OA\Response(response: 200, description: 'Token JWT', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'token', type: 'string'),
    ]))]
    #[OA\Response(response: 401, description: 'INVALID_CREDENTIALS – zły e-mail lub hasło', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[Security(name: null)]
    public function login(): JsonResponse
    {
        throw new ApiException('Request body must be JSON with email and password', 400, 'BAD_REQUEST');
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    #[IsGranted(User::ROLE_PLAYER)]
    #[OA\Get(summary: 'Dane zalogowanego użytkownika')]
    #[OA\Response(response: 200, description: 'Zalogowany użytkownik', content: new Model(type: User::class, groups: ['user:read']))]
    #[OA\Response(response: 401, description: 'UNAUTHORIZED, INVALID_TOKEN, TOKEN_EXPIRED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }
}
