<?php

namespace Bete\Tests;

use stdClass;
use PHPUnit\Framework\TestCase;
use Bete\Foundation\Application;

class StackTest extends TestCase
{
    public function testPushAndPop()
    {

        $app = new Application;
        $class = new stdClass;
        $app->singleton('class', function() use ($class) {
            return $class;
        });
        $this->assertSame($class, $app->make('class'));

        $app->bind('name', function() {
            return new stdClass;
        });
        $name1 = $app->make('name');
        $name2 = $app->make('name');
        $this->assertNotSame($name1, $name2);

        $class = new stdClass;
        $app->instance('instance', $class);
        $this->assertSame($class, $app->make('instance'));


        $stack = [];
        $this->assertEquals(0, count($stack));

        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertEquals(1, count($stack));

        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));
    }
}
