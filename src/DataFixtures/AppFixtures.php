<?php

namespace App\DataFixtures;

use App\Entity\Rarity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $aDataFixtures = [
            [
                'name' => 'commune',
                'grade' => 0
            ],
            [
                'name' => 'rare',
                'grade' => 1
            ],
            [
                'name' => 'super rare',
                'grade' => 2
            ],
            [
                'name' => 'ultra rare',
                'grade' => 3
            ],
            [
                'name' => 'ultimate rare',
                'grade' => 4
            ],
            [
                'name' => 'secret rare',
                'grade' => 5
            ],
        ];
        
        foreach ($aDataFixtures as $rarityFixture) {
            $rarity = new Rarity();
            $rarity->setName($rarityFixture['name'])
                   -> setGrade($rarityFixture['grade']);

            $manager->persist($rarity);
        }
        $manager->flush();
    }
}
