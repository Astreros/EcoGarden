<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

class UserController extends AbstractController
{
    public function __construct(private readonly SerializerInterface $serializer,
                                private readonly ValidatorInterface $validator,
                                private readonly EntityManagerInterface $entityManager,
                                private readonly UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    #[Route('/api/user', name: 'createUser', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Ajouter un conseil', required: true, content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'email', description: 'Adresse email', type: 'string'),
            new OA\Property(property: 'password', description: 'Mot de passe', type: 'string'),
            new OA\Property(property: 'city', description: 'Ville de l\'utilisateur', type: 'string'),
        ], type: 'object'
    )
    )]
    #[OA\Response(
        response: 204,
        description: 'No Content'
    )]
    #[OA\Tag(name: 'User')]
    public function createUser(Request $request): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $user->setPassword($this->userPasswordHasher->hashPassword($user, $user->getPassword()));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $jsonUser = $this->serializer->serialize($user, 'json');
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }

    /**
     * @throws JsonException
     * @throws \JsonException
     */
    #[Route('/api/user/{id}', name: 'updateUser', methods: ['PUT'])]
    #[OA\RequestBody(
        description: 'Ajouter un conseil', required: false, content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'email', description: 'Adresse email', type: 'string'),
            new OA\Property(property: 'roles', description: 'Role ["ROLE_USER"] ou ["ROLE_ADMIN"]', type: 'array', items: new OA\Items(type: 'string')),
            new OA\Property(property: 'password', description: 'Mot de passe', type: 'string'),
            new OA\Property(property: 'city', description: 'Ville de l\'utilisateur', type: 'string'),
        ], type: 'object'
    )
    )]
    #[OA\Response(
        response: 204,
        description: 'No Content'
    )]
    #[OA\Tag(name: 'User')]
    public function updateUser(Request $request, User $currentUser): JsonResponse
    {
        $updatedUser = $this->serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);

        $errors = $this->validator->validate($updatedUser);
        if (count($errors) > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if ($data['password'] !== null) {
            $updatedUser->setPassword($this->userPasswordHasher->hashPassword($updatedUser, $updatedUser->getPassword()));
        }

        $this->entityManager->persist($updatedUser);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/user/{id}', name: 'deleteUser', methods: ['DELETE'])]
    #[OA\Response(
        response: 204,
        description: 'No Content'
    )]
    #[OA\Tag(name: 'User')]
    public function deleteUser(User $user): JsonResponse
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
