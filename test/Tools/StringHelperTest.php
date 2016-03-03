<?php

namespace Ctrl\Common\Test\Tools;

use Ctrl\Common\Tools\StringHelper;

class StringHelperTest extends \PHPUnit_Framework_TestCase
{
    public function test_unpack()
    {
        $result = StringHelper::bracesToArray('test');
        self::assertEquals(['test'], $result);

        $result = StringHelper::bracesToArray('(test)');
        self::assertEquals(['test'], $result);

        $result = StringHelper::bracesToArray('(((test)))');
        self::assertEquals(['test'], $result);

        $result = StringHelper::bracesToArray('(test)(test2)');
        self::assertEquals([['test'], ['test2']], $result);

        $result = StringHelper::bracesToArray('(test) and (test2)');
        self::assertEquals([['test'], 'and', ['test2']], $result);

        $result = StringHelper::bracesToArray('test (test2)');
        self::assertEquals(['test', ['test2']], $result);

        $result = StringHelper::bracesToArray('(test (test2))');
        self::assertEquals([['test', ['test2']]], $result);

        $result = StringHelper::bracesToArray('(test) test3');
        self::assertEquals([['test'], 'test3'], $result);

        $result = StringHelper::bracesToArray('((test) test3)');
        self::assertEquals([[['test'], 'test3']], $result);

        $result = StringHelper::bracesToArray('(test) and (test3 (test5))');
        self::assertEquals([['test'], 'and', ['test3', ['test5']]], $result);

        $result = StringHelper::bracesToArray('(test) and ((test2) or (test3))');
        self::assertEquals([['test'], 'and', [['test2'], 'or', ['test3']]], $result);

        $result = StringHelper::bracesToArray('(test) and (test2) or (test3)');
        self::assertEquals([['test'], 'and', ['test2'], 'or', ['test3']], $result);

        $result = StringHelper::bracesToArray('(test) or (test2) and (test3)');
        self::assertEquals([['test'], 'or', ['test2'], 'and', ['test3']], $result);
    }
}
