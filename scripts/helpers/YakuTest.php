<?php

require_once __DIR__ . '/Yaku.php';

class ToLocalYakuTest extends PHPUnit_Framework_TestCase {
    public function setUp() {}
    public function tearDown() {}

    public function testValidConvert() {
        $actual = YakuHelper::getLocalYaku('1,1,2,1,0,1,54,1,53,3', null);
        $expected = ['yaku' => [33, 36, 35], 'dora' => '4'];
        sort($actual['yaku']);
        sort($expected['yaku']);
        $this->assertEquals($actual['dora'], $expected['dora']);
        $this->assertEquals($actual['yaku'], $expected['yaku']);
    }

    public function testValidHanSum() {
        $this->assertEquals(YakuHelper::getHanSum('1,1,2,1,0,1,54,1,53,3'), 7);
    }
}

