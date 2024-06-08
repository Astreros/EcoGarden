<?php

namespace App\DataFixtures;

use App\Entity\Advice;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Random\RandomException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    /**
     * @throws RandomException
     */
    public function load(ObjectManager $manager): void
    {
        // Création d'un utilisateur ROLE_USER
        $user = new User();
        $user->setEmail('user@ecogarden.com')->setCity('Lyon')->setRoles(['ROLE_USER']);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
        $manager->persist($user);

        // Création d'un utilisateur ROLE_ADMIN
        $userAdmin = new User();
        $userAdmin->setEmail('admin@ecogarden.com')->setCity('Montpellier')->setRoles(['ROLE_ADMIN']);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, 'password'));
        $manager->persist($userAdmin);

        // Création des conseils
        for ($i = 0; $i < 20; $i++) {
            $advice = new Advice();
            $advice->setMonth(random_int(1, 12))->setAdviceText("Conseil n° " . $i);

            $manager->persist($advice);
        }

        $manager->flush();
    }
}
