<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixturesTest extends KernelTestCase
{

    public function tearDown(): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $connection->executeQuery('DELETE FROM task');
        $connection->executeQuery('DELETE FROM user');
    }

    public function testLoad(): void
    {
        self::bootKernel();
        $entityManager = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $userRepository = $entityManager->getRepository(User::class);
        $taskRepository = $entityManager->getRepository(Task::class);

        $fixtures = new AppFixtures($hasher, $userRepository);
        $fixtures->load($entityManager);

        $allUsers = $userRepository->findAll();
        $allTasks = $taskRepository->findAll();

        $adminUsers = [];
        $classicUsers = [];

        foreach($allUsers as $user){
            if(in_array('ROLE_ADMIN', $user->getRoles())){
                $adminUsers[] = $user;
            }else{
                $classicUsers[] = $user;
            }

            $this->assertInstanceOf(User::class, $user);
        }

        $this->assertEquals(1, count($adminUsers));
        $this->assertEquals(4, count($classicUsers));
        $this->assertEquals(5, count($allUsers));

        foreach($allTasks as $task){
            $this->assertInstanceOf(Task::class, $task);
        }

        $this->assertEquals(9, count($allTasks));    
    }

}