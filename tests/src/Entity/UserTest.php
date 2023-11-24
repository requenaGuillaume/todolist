<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;
    private Task $task;

    public function setUp(): void
    {
        $this->user = new User();
        $this->task = new Task();
    }

    public function testGetSalt(): void
    {
        $salt = $this->user->getSalt();

        $this->assertNull($salt);
    }

    public function testAddAndGetTask(): void
    {
        $this->user->addTask($this->task);

        $tasks = new ArrayCollection([$this->task]);

        $this->assertEquals($tasks, $this->user->getTasks());
    }

    public function testRemoveTask(): void
    {
        $this->user->addTask($this->task);        
        $this->user->removeTask($this->task);
        
        $tasks = new ArrayCollection([]);

        $this->assertEquals($tasks, $this->user->getTasks());
    }

}