<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $user = new User();
            $user->setEmail("test{$i}@example.com");
            $user->setRoles(['ROLE_USER']);

            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                'test123'
            );
            $user->setPassword($hashedPassword);
            $manager->persist($user);
            echo "Created test user {$i}: test{$i}@example.com\n";
        }

        $manager->flush();
        echo "Users have been persisted to database\n";
    }
}