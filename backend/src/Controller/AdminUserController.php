<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/users')]
#[OA\Tag(name: 'Admin – użytkownicy')]
class AdminUserController extends AbstractController
{
    public function __construct(private UserService $userService)
    {
    }

    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
    #[OA\Get(summary: 'Lista użytkowników')]
    #[OA\Response(response: 200, description: 'Użytkownicy posortowani po nazwisku', content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(ref: new Model(type: User::class, groups: ['user:read'])),
    ))]
    #[OA\Response(response: 401, description: 'UNAUTHORIZED, INVALID_TOKEN, TOKEN_EXPIRED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 403, description: 'FORBIDDEN – wymagana rola ADMIN', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function list(): JsonResponse
    {
        return $this->json($this->userService->getAll(), Response::HTTP_OK, [], ['groups' => 'user:read']);
    }
}
