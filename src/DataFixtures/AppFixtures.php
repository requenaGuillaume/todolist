<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private UserRepository $userRepository
    )
    {
        
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('toto@toto.toto')
            ->setRoles(['ROLE_ADMIN'])
            ->setUsername('toto')
            ->setPassword($this->hasher->hashPassword($admin, 'toto'));

        $manager->persist($admin);

        $anonymous = $this->userRepository->findOneBy(['username' => 'anonyme']);

        if(!$anonymous){
            $anonymous = new User();
            $anonymous->setEmail('anonymous@unexistant.dummy')
                ->setRoles(['ROLE_USER'])
                ->setUsername('anonyme')
                ->setPassword($this->hasher->hashPassword($anonymous, 'password'));

            $manager->persist($anonymous);
        }

        for($i = 0; $i < 3; $i++){
            $this->createTask($anonymous, $i, $manager);
        }

        for($i = 3; $i < 6; $i++){
            $this->createTask($admin, $i, $manager);
        }

        $manager->flush();
    }

    private function createTask(User $user, int $i, ObjectManager $manager): void
    {
        $task = new Task();
        $task->setTitle("Title #$i")
            ->setContent("Content #$i")
            ->setCreatedAt(new DateTimeImmutable())
            ->setUser($user);

        $manager->persist($task);
    }
}
