<?php

require_once __DIR__ . '/Points.php';

class PointsTest extends PHPUnit_Framework_TestCase {
    public function setUp() {}
    public function tearDown() {}

    public function testRonBasic() {
        $this->assertEquals(Points::getRonPoints(2, 50, false), 3200);
        $this->assertEquals(Points::getRonPoints(3, 40, false), 5200);
    }

    public function testRonBasicDealer() {
        $this->assertEquals(Points::getRonPoints(2, 50, true), 4800);
        $this->assertEquals(Points::getRonPoints(3, 40, true), 7700);
    }

    public function testRonLimit() {
        $this->assertEquals(Points::getRonPoints(4, 40, false), 8000);
        $this->assertEquals(Points::getRonPoints(6, 40, false), 12000);
    }

    public function testRonLimitDealer() {
        $this->assertEquals(Points::getRonPoints(4, 40, true), 12000);
        $this->assertEquals(Points::getRonPoints(6, 40, true), 18000);
    }

    public function testTsumoBasic() {
        $this->assertEquals(Points::getTsumoPoints(2, 50), ['dealer' => 1600, 'player' => 800]);
        $this->assertEquals(Points::getTsumoPoints(3, 40), ['dealer' => 2600, 'player' => 1300]);
    }

    public function testTsumoLimit() {
        $this->assertEquals(Points::getTsumoPoints(4, 40), ['dealer' => 4000, 'player' => 2000]);
        $this->assertEquals(Points::getTsumoPoints(6, 40), ['dealer' => 6000, 'player' => 3000]);
    }
}

