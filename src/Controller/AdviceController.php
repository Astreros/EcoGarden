<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

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
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des conseils pour le mois en cours',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Advice::class))
        )
    )]
    #[OA\Tag(name: 'Advices')]
    public function getAllAdvices(): JsonResponse
    {
        $currentMonth = date("m");

        return $this->getAdvicesForMonth($currentMonth);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/advices/{month}', name: 'monthAdvices', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des conseils pour un mois donné',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Advice::class))
        )
    )]
    #[OA\Tag(name: 'Advices')]
    public function getAdvicesForMonth(int $month = null): JsonResponse
    {
        if ($month < 1 || $month > 12) {
            throw new BadRequestHttpException('Invalid monts value : month must be between 1 and 12');
        }

        $idCache = "getAllAdvices" . $month;

        $jsonAdvices = $this->cache->get($idCache, function (ItemInterface $item) use ($month) {
            $item->tag('advicesCache');
            $item->expiresAfter(86400);

            $advices = $this->adviceRepository->findAdvicesByMonth($month);

            return $this->serializer->serialize($advices, 'json');
        });

        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }

    #[Route('/api/advices', name: 'createAdvice', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Ajouter un conseil', required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'month', description: 'Mois du conseil (entre 1 et 12)', type: 'string'),
                new OA\Property(property: 'adviceText', description: 'Contenu du conseil', type: 'string'),
            ], type: 'object'
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Renvoie le conseil nouvellement créé',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Advice::class))
        )
    )]
    #[OA\Tag(name: 'Advices')]
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
    #[OA\RequestBody(
        description: 'Ajouter un conseil', required: false, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'month', description: 'Mois du conseil (entre 1 et 12)', type: 'string'),
                new OA\Property(property: 'adviceText', description: 'Contenu du conseil', type: 'string'),
            ], type: 'object'
        )
    )]
    #[OA\Response(
        response: 204,
        description: 'No Content'
    )]
    #[OA\Tag(name: 'Advices')]
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
    #[OA\Response(
        response: 204,
        description: 'No Content'
    )]
    #[OA\Tag(name: 'Advices')]
    public function deleteAdvice(Advice $advice): JsonResponse
    {
        $this->entityManager->remove($advice);
        $this->entityManager->flush();

        $this->cache->invalidateTags(["advicesCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
