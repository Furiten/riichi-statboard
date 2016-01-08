<?php

require_once __DIR__ . '/PointsCalc.php';

class PointsCalcTest extends PHPUnit_Framework_TestCase {
    protected $_users = [
        'heilage',
        'jun',
        'frontier',
        'manabi'
    ];

    /** @var PointsCalc */
    protected $_calc;

    public function setUp() {
        $this->_calc = new PointsCalc();
        $this->_calc->setPlayersList($this->_users);
    }

    public function tearDown() {
        $this->_calc = null;
    }

    // ron

    public function testRon() {
        $this->_calc->registerRon(3, 40, 'jun', 'frontier', 0, [], 0, 'heilage');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 35200);
        $this->assertEquals($result['frontier'], 24800);
    }

    public function testRonWithHonba() {
        $this->_calc->registerRon(3, 40, 'jun', 'frontier', 2, [], 0, 'heilage');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 35800);
        $this->assertEquals($result['frontier'], 24200);
    }

    public function testRonWithRiichiFromPrevRound() {
        $this->_calc->registerRon(3, 40, 'jun', 'frontier', 0, [], 1, 'heilage');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 36200);
        $this->assertEquals($result['frontier'], 24800);
    }

    public function testRonWithRiichi() {
        $this->_calc->registerRon(3, 40, 'jun', 'frontier', 0, ['heilage'], 1, 'heilage');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 36200);
        $this->assertEquals($result['frontier'], 24800);
        $this->assertEquals($result['heilage'], 29000);
    }

    // dealer ron

    public function testDealerRon() {
        $this->_calc->registerRon(3, 40, 'jun', 'frontier', 0, [], 0, 'jun');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 37700);
        $this->assertEquals($result['frontier'], 22300);
    }

    public function testDealerRonWithHonba() {
        $this->_calc->registerRon(3, 40, 'jun', 'frontier', 2, [], 0, 'jun');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 38300);
        $this->assertEquals($result['frontier'], 21700);
    }

    public function testDealerRonWithRiichiFromPrevRound() {
        $this->_calc->registerRon(3, 40, 'jun', 'frontier', 0, [], 1, 'jun');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 38700);
        $this->assertEquals($result['frontier'], 22300);
    }

    public function testDealerRonWithRiichi() {
        $this->_calc->registerRon(3, 40, 'jun', 'frontier', 0, ['heilage'], 1, 'jun');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 38700);
        $this->assertEquals($result['frontier'], 22300);
        $this->assertEquals($result['heilage'], 29000);
    }

    // tsumo

    public function testTsumo() {
        $this->_calc->registerTsumo(3, 40, 'jun', 0, [], 0, 'heilage');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 35200);
        $this->assertEquals($result['frontier'], 28700);
        $this->assertEquals($result['heilage'], 27400);
        $this->assertEquals($result['manabi'], 28700);
    }

    public function testTsumoWithHonba() {
        $this->_calc->registerTsumo(3, 40, 'jun', 2, [], 0, 'heilage');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 35800);
        $this->assertEquals($result['frontier'], 28500);
        $this->assertEquals($result['heilage'], 27200);
        $this->assertEquals($result['manabi'], 28500);
    }

    public function testTsumoWithRiichiFromPrevRound() {
        $this->_calc->registerTsumo(3, 40, 'jun', 0, [], 1, 'heilage');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 36200);
        $this->assertEquals($result['frontier'], 28700);
        $this->assertEquals($result['heilage'], 27400);
        $this->assertEquals($result['manabi'], 28700);
    }

    public function testTsumoWithRiichi() {
        $this->_calc->registerTsumo(3, 40, 'jun', 0, ['heilage'], 1, 'heilage');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 36200);
        $this->assertEquals($result['frontier'], 28700);
        $this->assertEquals($result['heilage'], 26400);
        $this->assertEquals($result['manabi'], 28700);
    }

    // dealer tsumo

    public function testDealerTsumo() {
        $this->_calc->registerTsumo(3, 40, 'jun', 0, [], 0, 'jun');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 37800);
        $this->assertEquals($result['frontier'], 27400);
        $this->assertEquals($result['heilage'], 27400);
        $this->assertEquals($result['manabi'], 27400);
    }

    public function testDealerTsumoWithHonba() {
        $this->_calc->registerTsumo(3, 40, 'jun', 2, [], 0, 'jun');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 38400);
        $this->assertEquals($result['frontier'], 27200);
        $this->assertEquals($result['heilage'], 27200);
        $this->assertEquals($result['manabi'], 27200);
    }

    public function testDealerTsumoWithRiichiFromPrevRound() {
        $this->_calc->registerTsumo(3, 40, 'jun', 0, [], 1, 'jun');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 38800);
        $this->assertEquals($result['frontier'], 27400);
        $this->assertEquals($result['heilage'], 27400);
        $this->assertEquals($result['manabi'], 27400);
    }

    public function testDealerTsumoWithRiichi() {
        $this->_calc->registerTsumo(3, 40, 'jun', 0, ['heilage'], 1, 'jun');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 38800);
        $this->assertEquals($result['frontier'], 27400);
        $this->assertEquals($result['heilage'], 26400);
        $this->assertEquals($result['manabi'], 27400);
    }

    // draw

    public function testDrawNoTempai() {
        $this->_calc->registerDraw([
            'jun' => 'noten',
            'frontier' => 'noten',
            'heilage' => 'noten',
            'manabi' => 'noten'
        ], []);
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 30000);
        $this->assertEquals($result['frontier'], 30000);
        $this->assertEquals($result['heilage'], 30000);
        $this->assertEquals($result['manabi'], 30000);
    }

    public function testDrawSingleTempai() {
        $this->_calc->registerDraw([
            'jun' => 'tempai',
            'frontier' => 'noten',
            'heilage' => 'noten',
            'manabi' => 'noten'
        ], []);
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 33000);
        $this->assertEquals($result['frontier'], 29000);
        $this->assertEquals($result['heilage'], 29000);
        $this->assertEquals($result['manabi'], 29000);
    }

    public function testDrawTwoTempai() {
        $this->_calc->registerDraw([
            'jun' => 'tempai',
            'frontier' => 'noten',
            'heilage' => 'noten',
            'manabi' => 'tempai'
        ], []);
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 31500);
        $this->assertEquals($result['frontier'], 28500);
        $this->assertEquals($result['heilage'], 28500);
        $this->assertEquals($result['manabi'], 31500);
    }

    public function testDrawThreeTempai() {
        $this->_calc->registerDraw([
            'jun' => 'tempai',
            'frontier' => 'noten',
            'heilage' => 'tempai',
            'manabi' => 'tempai'
        ], []);
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 31000);
        $this->assertEquals($result['frontier'], 27000);
        $this->assertEquals($result['heilage'], 31000);
        $this->assertEquals($result['manabi'], 31000);
    }

    public function testDrawAllTempai() {
        $this->_calc->registerDraw([
            'jun' => 'tempai',
            'frontier' => 'tempai',
            'heilage' => 'tempai',
            'manabi' => 'tempai'
        ], []);
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 30000);
        $this->assertEquals($result['frontier'], 30000);
        $this->assertEquals($result['heilage'], 30000);
        $this->assertEquals($result['manabi'], 30000);
    }

    public function testDrawWithRiichi() {
        $this->_calc->registerDraw([
            'jun' => 'tempai',
            'frontier' => 'tempai',
            'heilage' => 'noten',
            'manabi' => 'tempai'
        ], ['heilage', 'jun']);
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 30000);
        $this->assertEquals($result['frontier'], 31000);
        $this->assertEquals($result['heilage'], 26000);
        $this->assertEquals($result['manabi'], 31000);
    }

    // chombo

    public function testChombo() {
        $this->_calc->registerChombo('jun', 'heilage');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 22000);
        $this->assertEquals($result['frontier'], 32000);
        $this->assertEquals($result['heilage'], 34000);
        $this->assertEquals($result['manabi'], 32000);
    }

    public function testDealerChombo() {
        $this->_calc->registerChombo('jun', 'jun');
        $result = $this->_calc->getResultPoints();
        $this->assertEquals($result['jun'], 18000);
        $this->assertEquals($result['frontier'], 34000);
        $this->assertEquals($result['heilage'], 34000);
        $this->assertEquals($result['manabi'], 34000);
    }
}