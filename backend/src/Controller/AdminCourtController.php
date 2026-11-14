<?php

namespace App\Controller;

use App\Dto\CourtRequest;
use App\Entity\Court;
use App\Service\CourtService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/courts')]
#[OA\Tag(name: 'Admin – korty')]
#[OA\Response(response: 401, description: 'UNAUTHORIZED, INVALID_TOKEN, TOKEN_EXPIRED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 403, description: 'FORBIDDEN – wymagana rola ADMIN', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
class AdminCourtController extends AbstractController
{
    public function __construct(private CourtService $courtService)
    {
    }

    #[Route('', name: 'api_admin_courts_list', methods: ['GET'])]
    #[OA\Get(summary: 'Lista kortów')]
    #[OA\Response(response: 200, description: 'Korty posortowane po nazwie', content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(ref: new Model(type: Court::class, groups: ['court:read'])),
    ))]
    public function list(): JsonResponse
    {
        return $this->json($this->courtService->getAll(), Response::HTTP_OK, [], ['groups' => 'court:read']);
    }

    #[Route('/{id}', name: 'api_admin_courts_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(summary: 'Szczegóły kortu')]
    #[OA\Response(response: 200, description: 'Kort', content: new Model(type: Court::class, groups: ['court:read']))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function get(int $id): JsonResponse
    {
        return $this->json($this->courtService->get($id), Response::HTTP_OK, [], ['groups' => 'court:read']);
    }

    #[Route('', name: 'api_admin_courts_create', methods: ['POST'])]
    #[OA\Post(summary: 'Dodanie kortu')]
    #[OA\Response(response: 201, description: 'Utworzony kort', content: new Model(type: Court::class, groups: ['court:read']))]
    #[OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'COURT_NAME_TAKEN – kort o tej nazwie już istnieje', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function create(#[MapRequestPayload] CourtRequest $request): JsonResponse
    {
        $court = $this->courtService->create($request);

        return $this->json($court, Response::HTTP_CREATED, [], ['groups' => 'court:read']);
    }

    #[Route('/{id}', name: 'api_admin_courts_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[OA\Put(summary: 'Edycja kortu, także aktywacja i dezaktywacja')]
    #[OA\Response(response: 200, description: 'Zmieniony kort', content: new Model(type: Court::class, groups: ['court:read']))]
    #[OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'COURT_NAME_TAKEN', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function update(int $id, #[MapRequestPayload] CourtRequest $request): JsonResponse
    {
        $court = $this->courtService->update($id, $request);

        return $this->json($court, Response::HTTP_OK, [], ['groups' => 'court:read']);
    }

    #[Route('/{id}', name: 'api_admin_courts_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Usunięcie kortu')]
    #[OA\Response(response: 204, description: 'Kort usunięty')]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'COURT_IN_USE – kort jest przypisany do turnieju lub meczu', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function delete(int $id): Response
    {
        $this->courtService->delete($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
