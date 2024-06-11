<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AdviceController extends AbstractController
{
    public function __construct(private readonly AdviceRepository $adviceRepository,
                                private readonly SerializerInterface $serializer,
                                private readonly ValidatorInterface $validator,
                                private readonly EntityManagerInterface $entityManager,
                                private readonly TagAwareCacheInterface $cache)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/advices', name: 'advices', methods: ['GET'])]
    public function getAllAdvices(): JsonResponse
    {
        $currentMonth = date("m");

        $idCache = "getAllAdvices" . $currentMonth;

        $jsonAdvices = $this->cache->get($idCache, function (ItemInterface $item) use ($currentMonth) {
            $item->tag('advicesCache');
            $item->expiresAfter(2592000);

            $advices = $this->adviceRepository->findAdvicesByMonth($currentMonth);

            return $this->serializer->serialize($advices, 'json');
        });

        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/advices/{month}', name: 'monthAdvices', methods: ['GET'])]
    public function getAdvicesForMonth(int $month): JsonResponse
    {
        $idCache = "getAllAdvices" . $month;

        $jsonAdvices = $this->cache->get($idCache, function (ItemInterface $item) use ($month) {
            $item->tag('advicesCache');
            $item->expiresAfter(2592000);

            $advices = $this->adviceRepository->findAdvicesByMonth($month);

            return $this->serializer->serialize($advices, 'json');
        });

        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
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

    /**
     * @throws InvalidArgumentException
     */
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

        $this->cache->invalidateTags(["advicesCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/advices/{id}', name: 'deleteAdvice', methods: ['DELETE'])]
    public function deleteAdvice(Advice $advice): JsonResponse
    {
        $this->entityManager->remove($advice);
        $this->entityManager->flush();

        $this->cache->invalidateTags(["advicesCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
