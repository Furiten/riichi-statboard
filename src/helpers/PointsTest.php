<?php

require_once __DIR__ . '/Points.php';

class PointsTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }
    public function tearDown()
    {
    }

    public function testRonBasic()
    {
        $this->assertEquals(3200, Points::getRonPoints(2, 50, false));
        $this->assertEquals(5200, Points::getRonPoints(3, 40, false));
    }

    public function testRonBasicDealer()
    {
        $this->assertEquals(4800, Points::getRonPoints(2, 50, true));
        $this->assertEquals(7700, Points::getRonPoints(3, 40, true));
    }

    public function testRonLimit()
    {
        $this->assertEquals(5200, Points::getRonPoints(4, 20, false), '4/20 ok');
        $this->assertEquals(7700, Points::getRonPoints(4, 30, false), '4/30 ok');
        $this->assertEquals(8000, Points::getRonPoints(4, 40, false), '4/40 ok');
        $this->assertEquals(12000, Points::getRonPoints(6, 40, false), '6/40 ok');
    }

    public function testRonLimitDealer()
    {
        $this->assertEquals(7700, Points::getRonPoints(4, 20, true), '4/20 ok');
        $this->assertEquals(11600, Points::getRonPoints(4, 30, true), '4/30 ok');
        $this->assertEquals(12000, Points::getRonPoints(4, 40, true), '4/40 ok');
        $this->assertEquals(18000, Points::getRonPoints(6, 40, true), '6/40 ok');
    }

    public function testTsumoBasic()
    {
        $this->assertEquals(['dealer' => 1600, 'player' => 800], Points::getTsumoPoints(2, 50));
        $this->assertEquals(['dealer' => 2600, 'player' => 1300], Points::getTsumoPoints(3, 40));
    }

    public function testTsumoLimit()
    {
        $this->assertEquals(['dealer' => 2600, 'player' => 1300], Points::getTsumoPoints(4, 20), '4/20 ok');
        $this->assertEquals(['dealer' => 3900, 'player' => 2000], Points::getTsumoPoints(4, 30), '4/30 ok');
        $this->assertEquals(['dealer' => 4000, 'player' => 2000], Points::getTsumoPoints(4, 40), '4/40 ok');
        $this->assertEquals(['dealer' => 6000, 'player' => 3000], Points::getTsumoPoints(6, 40), '6/40 ok');
    }
}
