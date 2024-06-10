<?php

namespace App\Services;

use App\Repository\UserRepository;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;

readonly class Utils
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function getUserCity(string $token): string
    {
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