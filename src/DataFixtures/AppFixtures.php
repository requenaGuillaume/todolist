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
    private ?ObjectManager $em;

    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private UserRepository $userRepository
    ) {
        $this->em = null;
    }

    public function load(ObjectManager $manager): void
    {
        $this->em = $manager;

        $admin = $this->createUser('toto@toto.toto', 'toto', 'ROLE_ADMIN', 'toto');
        $anonymous = $this->userRepository->findOneBy(['username' => 'anonyme']);

        if(!$anonymous) {
            $anonymous = $this->createUser('anonymous@unexistant.dummy', 'anonyme', 'ROLE_USER', 'password');
        }

        for($i = 0; $i < 3; $i++) {
            $this->createTask($anonymous, $i);
        }

        for($i = 3; $i < 6; $i++) {
            $this->createTask($admin, $i);
        }

        for($i = 7; $i < 10; $i++) {
            $user = $this->createUser("user$i@mail.com", "User #$i", 'ROLE_USER', 'password');
            $this->createTask($user, $i);
        }

        $manager->flush();
    }

    private function createTask(User $user, int $i): void
    {
        $task = new Task();
        $task->setTitle("Title #$i")
            ->setContent("Content #$i")
            ->setCreatedAt(new DateTimeImmutable())
            ->setUser($user);

        $this->em->persist($task);
    }

    private function createUser(
        string $email,
        string $username,
        string $role,
        string $password
    ): User {
        $user = new User();
        $user->setEmail($email)
            ->setRoles([$role])
            ->setUsername($username)
            ->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);

        return $user;
    }
}
