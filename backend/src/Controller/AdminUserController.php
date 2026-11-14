<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/users')]
class AdminUserController extends AbstractController
{
    public function __construct(private UserService $userService)
    {
    }

    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->userService->getAll(), Response::HTTP_OK, [], ['groups' => 'user:read']);
    }
}
