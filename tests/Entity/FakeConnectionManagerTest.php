<?php

use Mini\Entity\Entity;
use Mini\Helpers\Fake\FakeConnectionManager;

class FakeConnectionManagerTest extends PHPUnit_Framework_TestCase
{
    public function testSimpleFixture()
    {
        $fixture = [['lala' => 1]];
        $manager = new FakeConnectionManager([
            '/.*/' => $fixture
        ]);
        $this->assertEquals(
            $fixture,
            $manager->getConnection('default')->select('SELECT * FROM users WHERE name = :name', ['lala'])
        );
    }

    public function testFixtureWithAssociativeParameters()
    {
        $fixture = [['lala' => 1]];
        $manager = new FakeConnectionManager([
            '/name = \'John\'/' => $fixture
        ]);
        $this->assertEquals(
            $fixture,
            $manager->getConnection('default')->select('SELECT * FROM users WHERE name = :name', ['name' => 'John'])
        );
        $this->assertEquals(
            [],
            $manager->getConnection('default')->select('SELECT * FROM users WHERE name = :name', ['name' => 'lala'])
        );
    }

    public function testFixtureWithPositionParameters()
    {
        $fixture = [['lala' => 1]];
        $manager = new FakeConnectionManager([
            '/name = \'John\' AND age = 25/' => $fixture
        ]);
        $this->assertEquals(
            $fixture,
            $manager->getConnection('default')->select('SELECT * FROM users WHERE name = ? AND age = ?', ['John', '25'])
        );
        $this->assertEquals(
            [],
            $manager->getConnection('default')->select('SELECT * FROM users WHERE name = ? AND age = ?', ['lala', '25'])
        );
    }
}
