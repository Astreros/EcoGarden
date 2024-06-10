<?php

namespace App\Controller;

use App\Services\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalWeatherApiController extends AbstractController
{
    private const API_URL = 'https://api.openweathermap.org/data/2.5/weather?q=';
    private const API_KEY = '&appid=d65cc6cc08368be1ac9f2d11d37d8405';
    private const UNITS = '&units=metric';
    private const LANG = '&lang=fr';


    public function __construct(private readonly Utils $utils,
                                private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/api/weather', name: 'weatherByCurrentUser', methods: ['GET'])]
    public function getWeatherByCurrentUser(Request $request): JsonResponse
    {
        $city = $this->utils->getUserCity($request);

        $response = $this->httpClient->request(
            "GET",
            self::API_URL . $city . self::API_KEY . self::UNITS . self::LANG
        );

        return new JsonResponse($response->getContent(), $response->getStatusCode(), [], true);
    }

    #[Route('/api/weather/{city}', name: 'weatherByCity', methods: ['GET'])]
    public function getWeatherByCity(): JsonResponse
    {
        return $this->json([]);
    }
}
