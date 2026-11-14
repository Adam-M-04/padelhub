<?php

namespace App\Controller;

use App\Entity\Registration;
use App\Entity\User;
use App\Service\RegistrationService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/tournaments/{id}', requirements: ['id' => Requirement::UUID])]
#[OA\Tag(name: 'Zapisy')]
class RegistrationController extends AbstractController
{
    private const GROUPS = ['groups' => ['registration:read', 'player:read']];

    public function __construct(private RegistrationService $registrationService)
    {
    }

    #[Route('/registrations', name: 'api_tournament_registrations', methods: ['GET'])]
    #[OA\Get(summary: 'Lista zapisanych i rezerwowych graczy')]
    #[OA\Response(response: 200, description: 'Zapisani i lista rezerwowa w kolejności zapisów', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'registered', type: 'array', items: new OA\Items(ref: new Model(type: Registration::class, groups: ['registration:read', 'player:read']))),
        new OA\Property(property: 'reserve', type: 'array', items: new OA\Items(ref: new Model(type: Registration::class, groups: ['registration:read', 'player:read']))),
    ]))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[Security(name: null)]
    public function list(Uuid $id): JsonResponse
    {
        return $this->json($this->registrationService->getList($id), Response::HTTP_OK, [], self::GROUPS);
    }

    #[Route('/registration', name: 'api_tournament_register', methods: ['POST'])]
    #[IsGranted(User::ROLE_PLAYER)]
    #[OA\Post(summary: 'Zapis zalogowanego gracza na turniej', description: 'Gdy limit miejsc jest wyczerpany, gracz trafia na listę rezerwową (status RESERVE).')]
    #[OA\Response(response: 201, description: 'Zapis', content: new Model(type: Registration::class, groups: ['registration:read', 'player:read']))]
    #[OA\Response(response: 401, description: 'UNAUTHORIZED, INVALID_TOKEN, TOKEN_EXPIRED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 404, description: 'NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'ALREADY_REGISTERED, REGISTRATION_CLOSED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function register(Uuid $id, #[CurrentUser] User $user): JsonResponse
    {
        $registration = $this->registrationService->register($id, $user);

        return $this->json($registration, Response::HTTP_CREATED, [], self::GROUPS);
    }

    #[Route('/registration', name: 'api_tournament_withdraw', methods: ['DELETE'])]
    #[IsGranted(User::ROLE_PLAYER)]
    #[OA\Delete(summary: 'Wypisanie zalogowanego gracza z turnieju', description: 'Zwolnione miejsce zajmuje pierwsza osoba z listy rezerwowej, która dostaje e-mail.')]
    #[OA\Response(response: 204, description: 'Gracz wypisany')]
    #[OA\Response(response: 401, description: 'UNAUTHORIZED, INVALID_TOKEN, TOKEN_EXPIRED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 404, description: 'NOT_FOUND – turniej nie istnieje albo gracz nie jest zapisany', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: 'REGISTRATION_CLOSED', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function withdraw(Uuid $id, #[CurrentUser] User $user): Response
    {
        $this->registrationService->withdraw($id, $user);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
