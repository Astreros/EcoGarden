<?php

namespace App\Controller;

use App\Services\Utils;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use OpenApi\Attributes as OA;

class ExternalWeatherApiController extends AbstractController
{
    private const API_URL = 'https://api.openweathermap.org/data/2.5/weather?q=';
    private const API_KEY = '&appid=35035d5f48e8f4029545a926450d1c51';
    private const UNITS = '&units=metric';
    private const LANG = '&lang=fr';


    public function __construct(private readonly Utils $utils,
                                private readonly HttpClientInterface $httpClient,
                                private readonly TagAwareCacheInterface $cache)
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     */
    #[Route('/api/weather', name: 'weatherByCurrentUser', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne la météo pour la ville de l\'utilisateur authentifié'
    )]
    #[OA\Tag(name: 'Weather')]
    public function getWeatherByCurrentUser(Request $request): JsonResponse
    {
        $city = $this->utils->getUserCity($request);

        return $this->getWeatherByCity($city);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     */
    #[Route('/api/weather/{city}', name: 'weatherByCity', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne la météo pour la ville demandé'
    )]
    #[OA\Tag(name: 'Weather')]
    public function getWeatherByCity(string $city): JsonResponse
    {
        $idCache = "getWeatherByCity" . $city;

        $jsonWeather = $this->cache->get($idCache, function (ItemInterface $item) use ($city) {

           $item->tag('weatherCache');
           $item->expiresAfter(3600);

           $response = $this->httpClient->request(
               "GET",
               self::API_URL . $city . self::API_KEY . self::UNITS . self::LANG
           );

            return $response->getContent();
        });

        return new JsonResponse(
            $jsonWeather, Response::HTTP_OK, [], true
        );
    }
}
