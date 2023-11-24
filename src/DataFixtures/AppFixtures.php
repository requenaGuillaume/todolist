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
        $admin = $this->createUser($manager, 'toto@toto.toto', 'toto', 'ROLE_ADMIN', 'toto');
        $anonymous = $this->userRepository->findOneBy(['username' => 'anonyme']);

        if(!$anonymous){
            $anonymous = $this->createUser($manager, 'anonymous@unexistant.dummy', 'anonyme', 'ROLE_USER', 'password');
        }

        for($i = 0; $i < 3; $i++){
            $this->createTask($anonymous, $i, $manager);
        }

        for($i = 3; $i < 6; $i++){
            $this->createTask($admin, $i, $manager);
        }

        for($i = 7; $i < 10; $i++){
            $user = $this->createUser($manager, "user$i@mail.com", "User #$i", 'ROLE_USER', 'password');
            $this->createTask($user, $i, $manager);
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

    private function createUser(
        ObjectManager $manager,
        string $email, 
        string $username, 
        string $role,
        string $password
    ): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setRoles([$role])
            ->setUsername($username)
            ->setPassword($this->hasher->hashPassword($user, $password));
        
        $manager->persist($user);

        return $user;
    }
}
