<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AdviceController extends AbstractController
{
    public function __construct(private readonly AdviceRepository $adviceRepository,
                                private readonly SerializerInterface $serializer,
                                private readonly ValidatorInterface $validator,
                                private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/advices', name: 'advices', methods: ['GET'])]
    public function getAllAdvices(): JsonResponse
    {
        $currentMonth = date("m");

        $advices = $this->adviceRepository->findAdvicesByMonth($currentMonth);
        $jsonAdvices = $this->serializer->serialize($advices, 'json');

        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }

    #[Route('/api/advices/{month}', name: 'monthAdvices', methods: ['GET'])]
    public function getAdvicesForMonth(int $month): JsonResponse
    {
        $monthAdvices = $this->adviceRepository->findAdvicesByMonth($month);
        $jsonMonthAdvices = $this->serializer->serialize($monthAdvices, 'json');

        return new JsonResponse($jsonMonthAdvices, Response::HTTP_OK, [], true);
    }

    #[Route('/api/advices', name: 'createAdvice', methods: ['POST'])]
    public function createAdvice(Request $request): JsonResponse
    {
        $advice = $this->serializer->deserialize($request->getContent(), Advice::class, 'json');

        $errors = $this->validator->validate($advice);
        if (count($errors) > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->persist($advice);
        $this->entityManager->flush();

        $jsonAdvice = $this->serializer->serialize($advice, 'json');
        return new JsonResponse($jsonAdvice, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/advices/{id}', name: 'updateAdvice', methods: ['PUT'])]
    public function updateAdvice(Request $request, Advice $currentAdvice): JsonResponse
    {
        $updatedAdvice = $this->serializer->deserialize($request->getContent(), Advice::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAdvice]);

        $errors = $this->validator->validate($updatedAdvice);
        if (count($errors) > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->persist($updatedAdvice);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/advices/{id}', name: 'deleteAdvice', methods: ['DELETE'])]
    public function deleteAdvice(Advice $advice): JsonResponse
    {
        $this->entityManager->remove($advice);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
