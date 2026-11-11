<?php

namespace App\Controller;

use App\Dto\CourtRequest;
use App\Service\CourtService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/courts')]
class AdminCourtController extends AbstractController
{
    public function __construct(private CourtService $courtService)
    {
    }

    #[Route('', name: 'api_admin_courts_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->courtService->getAll(), Response::HTTP_OK, [], ['groups' => 'court:read']);
    }

    #[Route('/{id}', name: 'api_admin_courts_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->json($this->courtService->get($id), Response::HTTP_OK, [], ['groups' => 'court:read']);
    }

    #[Route('', name: 'api_admin_courts_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CourtRequest $request): JsonResponse
    {
        $court = $this->courtService->create($request);

        return $this->json($court, Response::HTTP_CREATED, [], ['groups' => 'court:read']);
    }

    #[Route('/{id}', name: 'api_admin_courts_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] CourtRequest $request): JsonResponse
    {
        $court = $this->courtService->update($id, $request);

        return $this->json($court, Response::HTTP_OK, [], ['groups' => 'court:read']);
    }

    #[Route('/{id}', name: 'api_admin_courts_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->courtService->delete($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
