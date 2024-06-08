<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    public function __construct(private readonly UserRepository $userRepository,
                                private readonly SerializerInterface $serializer,
                                private readonly ValidatorInterface $validator,
                                private readonly EntityManagerInterface $entityManager,
                                private readonly UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    #[Route('/api/user', name: 'createUser', methods: ['POST'])]
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

    #[Route('/api/user/{id}', name: 'updateUser', methods: ['PUT'])]
    public function updateUser(): JsonResponse
    {

    }

    #[Route('/api/user/{id}', name: 'deleteUser', methods: ['DELETE'])]
    public function deleteUser(): JsonResponse
    {

    }
}
