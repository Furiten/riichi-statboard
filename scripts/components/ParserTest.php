<?php

require_once __DIR__ . '/Parser.php';

class ParserTest extends PHPUnit_Framework_TestCase
{
    protected $_users = [
        'heilage' => 1,
        'jun' => 1,
        'frontier' => 1,
        'manabi' => 1
    ];
    /**
     * @var Parser
     */
    protected $_parser;
    /**
     * @var Tokenizer
     */
    protected $_tokenizer;
    protected $_callbacks = [];
    protected $_hooks = [];

    public function __construct()
    {
        $this->_callbacks['usual'] = function ($input) {
            if (!empty($this->_hooks['usual']) && is_callable($this->_hooks['usual'])) {
                $this->_hooks['usual']($input);
            }
        };
        $this->_callbacks['yakuman'] = function ($input) {
            if (!empty($this->_hooks['yakuman']) && is_callable($this->_hooks['yakuman'])) {
                $this->_hooks['yakuman']($input);
            }
        };
        $this->_callbacks['draw'] = function ($input) {
            if (!empty($this->_hooks['draw']) && is_callable($this->_hooks['draw'])) {
                $this->_hooks['draw']($input);
            }
        };
        $this->_callbacks['chombo'] = function ($input) {
            if (!empty($this->_hooks['chombo']) && is_callable($this->_hooks['chombo'])) {
                $this->_hooks['chombo']($input);
            }
        };
        $this->_callbacks['tokenizerCb'] = function ($input) {
            if (!empty($this->_hooks['tokenizerCb']) && is_callable($this->_hooks['tokenizerCb'])) {
                $this->_hooks['tokenizerCb']($input);
            }
        };
    }

    public function setUp()
    {
        $this->_parser = new Parser(
            $this->_callbacks['usual'],
            $this->_callbacks['yakuman'],
            $this->_callbacks['draw'],
            $this->_callbacks['chombo'],
            $this->_users
        );

        $this->_tokenizer = new Tokenizer($this->_callbacks['tokenizerCb']);
    }

    public function tearDown()
    {
        $this->_parser = null;
        $this->_tokenizer = null;
        $this->_hooks = [
            'usual' => function () {
                throw new Exception('Unexpected handler call: usual');
            },
            'yakuman' => function () {
                throw new Exception('Unexpected handler call: yakuman');
            },
            'draw' => function () {
                throw new Exception('Unexpected handler call: draw');
            },
            'chombo' => function () {
                throw new Exception('Unexpected handler call: chombo');
            },
            'tokenizerCb' => function () {
                throw new Exception('Unexpected handler call: tokenizerCb');
            }
        ];
    }

    protected function _tokenize($str)
    {
        $this->_tokenizer->_reassignLastAllowedToken([Tokenizer::OUTCOME => 1]);

        $tokens = preg_split('#\s+#', $str);
        foreach ($tokens as $t) {
            $this->_tokenizer->nextToken($t);
        }
        $tokens = [];
        $this->_hooks['tokenizerCb'] = function($statement) use (&$tokens) {
            $tokens []= $statement;
        };
        $this->_tokenizer->callTokenEof();
        return reset($tokens);
    }

    public function testSplitMultiRon()
    {
        $validTokens = $this->_tokenize('ron heilage from frontier 1han 30fu riichi heilage manabi
                                         also jun 2han 30fu riichi frontier');

        list($actual, $loser) = $this->_parser->_iSplitMultiRon($validTokens);
        $strActual = array_map(function($el) {
            return array_reduce($el, function($acc, $el2) {
                return $acc . ' ' . (string)$el2;
            }, '');
        }, $actual);

        $this->assertEquals('frontier', $loser);
        $this->assertEquals('heilage 1han 30fu riichi heilage manabi', trim($strActual[0]));
        $this->assertEquals('jun 2han 30fu riichi frontier', trim($strActual[1]));
    }

    public function testAssignRiichiBets()
    {
        $validTokens = $this->_tokenize('ron heilage from frontier 1han 30fu riichi heilage manabi
                                         also jun 2han 30fu riichi frontier');

        $actual = $this->_parser->_iAssignRiichiBets($validTokens, ['frontier' => 1, 'heilage' => 1, 'jun' => 1, 'manabi' => 1]);
        $expected = [
            'heilage' => [
                'riichi' => ['heilage', 'manabi', 'frontier'],
                'riichi_totalCount' => 3
            ],
            'jun' => [
                'riichi' => [],
                'riichi_totalCount' => 0
            ]
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testTempaiParse()
    {
        $validTokens = $this->_tokenize('draw tempai manabi jun');
        $expected = [
            'manabi',
            'jun'
        ];

        $actual = $this->_parser->_iGetTempai($validTokens, ['manabi' => 1, 'jun' => 1]);
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testRiichiParse()
    {
        $validTokens = $this->_tokenize('ron heilage 1han 30fu riichi manabi jun');
        $expected = [
            'manabi',
            'jun'
        ];

        $actual = $this->_parser->_iGetRiichi($validTokens, ['manabi' => 1, 'jun' => 1]);
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testEmptyLog()
    {
        $validText = 'heilage:23200 frontier:23300 jun:43000 manabi:12000';
        $expected = [
            'heilage' => '23200',
            'jun' => '43000',
            'frontier' => '23300',
            'manabi' => '12000'
        ];
        $actual = $this->_parser->parse($validText);

        ksort($expected);
        ksort($actual['scores']);
        $this->assertEquals($expected, $actual['scores']);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 100
     */
    public function testInvalidHeader()
    {
        $invalidText = 'heilage: 23300 frontier:33200';
        $this->_parser->parse($invalidText);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 101
     */
    public function testMistypedUserHeader()
    {
        $mistypedUserText = 'heliage:23200 frontier:23300 jun:43000 manabi:12000';
        $this->_parser->parse($mistypedUserText);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 106
     */
    public function testInvalidOutcome()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      rodn heilage 1han 30fu riichi manabi jun';
        $this->_parser->parse($invalidText);
    }

    public function testBasicRon()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      ron heilage from frontier 1han 30fu';
        $expected = [
            'dealer' => false,
            'fu' => '30',
            'han' => '1',
            'honba' => 0,
            'outcome' => 'ron',
            'riichi' => [],
            'riichi_totalCount' => 0,
            'round' => 1,
            'winner' => 'heilage',
            'loser' => 'frontier',
            'yakuman' => false
        ];
        $this->_hooks['usual'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(2, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(1, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(0, $this->_parser->_getHonba());
    }

    public function testBasicDoubleRon()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      ron heilage from frontier 1han 30fu
                      also jun 2han 30fu';

        $idx = 0;
        $expected = [
            [
                'dealer' => false,
                'multiRon' => 2,
                'fu' => '30',
                'han' => '1',
                'honba' => 0,
                'outcome' => 'ron',
                'riichi' => [],
                'riichi_totalCount' => 0,
                'round' => 1,
                'winner' => 'heilage',
                'loser' => 'frontier',
                'yakuman' => false
            ],
            [
                'dealer' => false,
                'multiRon' => 2,
                'fu' => '30',
                'han' => '2',
                'honba' => 0,
                'outcome' => 'ron',
                'riichi' => [],
                'riichi_totalCount' => 0,
                'round' => 1,
                'winner' => 'jun',
                'loser' => 'frontier',
                'yakuman' => false
            ]
        ];
        $this->_hooks['usual'] = function ($data) use ($expected, &$idx) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected[$idx++], $data);
        };

        $this->_parser->parse($validText);
        $this->assertEquals(2, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(1, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(0, $this->_parser->_getHonba());
    }

    public function testDealerRon()
    {
        $validText = 'heilage:23200 frontier:23300 jun:43000 manabi:12000
                      ron heilage from frontier 1han 30fu';
        $expected = [
            'dealer' => true,
            'fu' => '30',
            'han' => '1',
            'honba' => 0,
            'outcome' => 'ron',
            'riichi' => [],
            'riichi_totalCount' => 0,
            'round' => 1,
            'winner' => 'heilage',
            'loser' => 'frontier',
            'yakuman' => false
        ];
        $this->_hooks['usual'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(1, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(0, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(1, $this->_parser->_getHonba());
    }

    public function testDealerDoubleRon()
    {
        $validText = 'heilage:23200 frontier:23300 jun:43000 manabi:12000
                      ron jun from frontier 1han 30fu
                      also heilage 2han 30fu';

        $idx = 0;
        $expected = [
            [
                'dealer' => false,
                'multiRon' => 2,
                'fu' => '30',
                'han' => '1',
                'honba' => 0,
                'outcome' => 'ron',
                'riichi' => [],
                'riichi_totalCount' => 0,
                'round' => 1,
                'winner' => 'jun',
                'loser' => 'frontier',
                'yakuman' => false
            ],
            [
                'dealer' => true,
                'multiRon' => 2,
                'fu' => '30',
                'han' => '2',
                'honba' => 0,
                'outcome' => 'ron',
                'riichi' => [],
                'riichi_totalCount' => 0,
                'round' => 1,
                'winner' => 'heilage',
                'loser' => 'frontier',
                'yakuman' => false
            ]
        ];
        $this->_hooks['usual'] = function ($data) use ($expected, &$idx) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected[$idx++], $data);
        };

        $this->_parser->parse($validText);
        $this->assertEquals(1, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(0, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(1, $this->_parser->_getHonba());
    }

    public function testRonWithRiichi()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      ron heilage from frontier 1han 30fu riichi manabi jun';
        $expected = [
            'dealer' => false,
            'fu' => '30',
            'han' => '1',
            'honba' => 0,
            'outcome' => 'ron',
            'riichi' => ['manabi', 'jun'],
            'riichi_totalCount' => 2,
            'round' => 1,
            'winner' => 'heilage',
            'loser' => 'frontier',
            'yakuman' => false
        ];
        $this->_hooks['usual'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(2, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(1, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(0, $this->_parser->_getHonba());
    }

    public function testDoubleRonWithRiichi()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      ron heilage from frontier 1han 30fu riichi heilage manabi
                      also jun 2han 30fu riichi frontier';

        $idx = 0;
        $expected = [
            [
                'dealer' => false,
                'multiRon' => 2,
                'fu' => '30',
                'han' => '1',
                'honba' => 0,
                'outcome' => 'ron',
                'riichi' => ['heilage', 'manabi', 'frontier'],
                'riichi_totalCount' => 3,
                'round' => 1,
                'winner' => 'heilage',
                'loser' => 'frontier',
                'yakuman' => false
            ],
            [
                'dealer' => false,
                'multiRon' => 2,
                'fu' => '30',
                'han' => '2',
                'honba' => 0,
                'outcome' => 'ron',
                'riichi' => [],
                'riichi_totalCount' => 0,
                'round' => 1,
                'winner' => 'jun',
                'loser' => 'frontier',
                'yakuman' => false
            ]
        ];
        $this->_hooks['usual'] = function ($data) use ($expected, &$idx) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected[$idx++], $data);
        };

        $this->_parser->parse($validText);
        $this->assertEquals(2, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(1, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(0, $this->_parser->_getHonba());
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 103
     */
    public function testInvalidRonNoLoser()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      ron heilage 1han 30fu riichi manabi jun';
        $this->_parser->parse($invalidText);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 104
     */
    public function testInvalidRonMistypedWinner()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      ron heliage from frontier 1han 30fu riichi manabi jun';
        $this->_parser->parse($invalidText);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 105
     */
    public function testInvalidRonMistypedLoser()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      ron heilage from forntier 1han 30fu riichi manabi jun';
        $this->_parser->parse($invalidText);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 107
     */
    public function testInvalidRonMistypedRiichi()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      ron heilage from frontier 1han 30fu riichi mpnabi jun';
        $this->_parser->parse($invalidText);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 108
     */
    public function testInvalidRonWrongRiichi()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      ron heilage from frontier 1han 30fu richi manabi jun';
        $this->_parser->parse($invalidText);
    }

    public function testBasicTsumo()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      tsumo heilage 1han 30fu';
        $expected = [
            'dealer' => false,
            'fu' => '30',
            'han' => '1',
            'honba' => 0,
            'outcome' => 'tsumo',
            'riichi' => [],
            'riichi_totalCount' => 0,
            'round' => 1,
            'winner' => 'heilage',
            'yakuman' => false
        ];
        $this->_hooks['usual'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(2, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(1, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(0, $this->_parser->_getHonba());
    }

    public function testDealerTsumo()
    {
        $validText = 'heilage:23200 frontier:23300 jun:43000 manabi:12000
                      tsumo heilage 1han 30fu';
        $expected = [
            'dealer' => true,
            'fu' => '30',
            'han' => '1',
            'honba' => 0,
            'outcome' => 'tsumo',
            'riichi' => [],
            'riichi_totalCount' => 0,
            'round' => 1,
            'winner' => 'heilage',
            'yakuman' => false
        ];
        $this->_hooks['usual'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(1, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(0, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(1, $this->_parser->_getHonba());
    }

    public function testTsumoWithRiichi()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      tsumo heilage 1han 30fu riichi manabi jun';
        $expected = [
            'dealer' => false,
            'fu' => '30',
            'han' => '1',
            'honba' => 0,
            'outcome' => 'tsumo',
            'riichi' => ['manabi', 'jun'],
            'riichi_totalCount' => 2,
            'round' => 1,
            'winner' => 'heilage',
            'yakuman' => false
        ];
        $this->_hooks['usual'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(2, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(1, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(0, $this->_parser->_getHonba());
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 104
     */
    public function testInvalidTsumoMistypedWinner()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      tsumo heliage 1han 30fu riichi manabi jun';
        $this->_parser->parse($invalidText);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 107
     */
    public function testInvalidTsumoMistypedRiichi()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      tsumo heilage 1han 30fu riichi mpnabi jun';
        $this->_parser->parse($invalidText);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 108
     */
    public function testInvalidTsumoWrongRiichi()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      tsumo heilage 1han 30fu richi manabi jun';
        $this->_parser->parse($invalidText);
    }

    public function testBasicDraw()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      draw tempai heilage jun';
        $expected = [
            'honba' => 0,
            'outcome' => 'draw',
            'riichi' => [],
            'riichi_totalCount' => 0,
            'round' => 1,
            'players_tempai' => [
                'frontier' => 'noten',
                'heilage' => 'tempai',
                'jun' => 'tempai',
                'manabi' => 'noten'
            ]
        ];

        $this->_hooks['draw'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(2, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(1, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(1, $this->_parser->_getHonba());
    }

    public function testDealerTempaiDraw()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      draw tempai frontier';
        $expected = [
            'honba' => 0,
            'outcome' => 'draw',
            'riichi' => [],
            'riichi_totalCount' => 0,
            'round' => 1,
            'players_tempai' => [
                'frontier' => 'tempai',
                'heilage' => 'noten',
                'jun' => 'noten',
                'manabi' => 'noten'
            ]
        ];

        $this->_hooks['draw'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(1, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(0, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(1, $this->_parser->_getHonba());
    }

    public function testDrawTempaiAll()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      draw tempai all';
        $expected = [
            'honba' => 0,
            'outcome' => 'draw',
            'riichi' => [],
            'riichi_totalCount' => 0,
            'round' => 1,
            'players_tempai' => [
                'frontier' => 'tempai',
                'heilage' => 'tempai',
                'jun' => 'tempai',
                'manabi' => 'tempai'
            ]
        ];

        $this->_hooks['draw'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(1, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(0, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(1, $this->_parser->_getHonba());
    }

    public function testDrawTempaiNone()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      draw tempai nobody';
        $expected = [
            'honba' => 0,
            'outcome' => 'draw',
            'riichi' => [],
            'riichi_totalCount' => 0,
            'round' => 1,
            'players_tempai' => [
                'frontier' => 'noten',
                'heilage' => 'noten',
                'jun' => 'noten',
                'manabi' => 'noten'
            ]
        ];

        $this->_hooks['draw'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(2, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(1, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(1, $this->_parser->_getHonba());
    }

    public function testDrawWithRiichi()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      draw tempai nobody riichi jun manabi';
        $expected = [
            'honba' => 0,
            'outcome' => 'draw',
            'riichi' => ['jun', 'manabi'],
            'riichi_totalCount' => 2,
            'round' => 1,
            'players_tempai' => [
                'frontier' => 'noten',
                'heilage' => 'noten',
                'jun' => 'noten',
                'manabi' => 'noten'
            ]
        ];

        $this->_hooks['draw'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(2, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(1, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(1, $this->_parser->_getHonba());
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 108
     */
    public function testInvalidDrawNoTempaiList()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      draw all riichi mpnabi jun';
        $this->_parser->parse($invalidText);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 117
     */
    public function testInvalidDrawMistypedTempai()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      draw tempai mpnabi jun';
        $this->_parser->parse($invalidText);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 107
     */
    public function testInvalidDrawMistypedRiichi()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      draw tempai all riichi mpnabi jun';
        $this->_parser->parse($invalidText);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 108
     */
    public function testInvalidDrawWrongRiichi()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      draw tempai all richi manabi jun';
        $this->_parser->parse($invalidText);
    }

    public function testWinAfterDrawWithRiichi()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      draw tempai nobody riichi jun manabi
                      ron manabi from jun 1han 30fu';
        $expectedDraw = [
            'honba' => 0,
            'outcome' => 'draw',
            'riichi' => ['jun', 'manabi'],
            'riichi_totalCount' => 2,
            'round' => 1,
            'players_tempai' => [
                'frontier' => 'noten',
                'heilage' => 'noten',
                'jun' => 'noten',
                'manabi' => 'noten'
            ]
        ];

        $expectedUsual = [
            'dealer' => false,
            'fu' => '30',
            'han' => '1',
            'honba' => 1,
            'outcome' => 'ron',
            'riichi' => [],
            'riichi_totalCount' => 2,
            'round' => 2,
            'winner' => 'manabi',
            'loser' => 'jun',
            'yakuman' => false
        ];

        $this->_hooks['draw'] = function ($data) use ($expectedDraw) {
            ksort($data);
            ksort($expectedDraw);
            $this->assertEquals($expectedDraw, $data);
        };

        $this->_hooks['usual'] = function ($data) use ($expectedUsual) {
            ksort($data);
            ksort($expectedUsual);
            $this->assertEquals($expectedUsual, $data);
        };

        $this->_parser->parse($validText);
        $this->assertEquals(3, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(2, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(0, $this->_parser->_getHonba());
        $this->assertEquals(0, $this->_parser->_getRiichiCount());
    }

    public function testBasicChombo()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      chombo heilage';
        $expected = [
            'dealer' => false,
            'loser' => 'heilage',
            'outcome' => 'chombo',
            'round' => 1
        ];

        $this->_hooks['chombo'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(1, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(0, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(0, $this->_parser->_getHonba());
    }

    public function testDealerChombo()
    {
        $validText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      chombo frontier';
        $expected = [
            'dealer' => true,
            'loser' => 'frontier',
            'outcome' => 'chombo',
            'round' => 1
        ];

        $this->_hooks['chombo'] = function ($data) use ($expected) {
            ksort($data);
            ksort($expected);
            $this->assertEquals($expected, $data);
        };
        $this->_parser->parse($validText);
        $this->assertEquals(1, $this->_parser->_getCurrentRound()); // starting from 1
        $this->assertEquals(0, $this->_parser->_getCurrentDealer()); // starting from 0
        $this->assertEquals(0, $this->_parser->_getHonba());
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 104
     */
    public function testInvalidChomboMistyped()
    {
        $invalidText = 'frontier:23200 heilage:23300 jun:43000 manabi:12000
                      chombo forntier';
        $this->_parser->parse($invalidText);
    }
}

