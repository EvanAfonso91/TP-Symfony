<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@admin.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('Admin');
        $admin->setPhoneNumber('0123456789');
        $admin->setRoles(['ROLE_ADMIN']);
        
        $password = $this->hasher->hashPassword($admin, 'admin');
        $admin->setPassword($password);

        $manager->persist($admin);
        $manager->flush();
    }
} 