<?php

namespace App\Services;

use App\Repository\UserRepository;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Symfony\Component\HttpFoundation\Request;

readonly class Utils
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function getUserCity(Request $request): string
    {
        $token = $request->headers->get('Authorization');
        $token = substr($token, 7); // Supprimer le préfixe "Bearer "

        $parser = new Parser(new JoseEncoder());
        $jwt = $parser->parse($token);

        $email = $jwt->claims()->get('username');
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        return $user->getCity();
    }
}