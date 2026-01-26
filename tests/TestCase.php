<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 20/04/2016
 * Time: 2:40 PM
 */

namespace Freshdesk\tests;

use Freshdesk\Api;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    abstract public static function methodsThatShouldExist(): array;

    protected Api $api;

    /**
     * The specific class being tested
     */
    protected mixed $class;

    protected function setUp(): void
    {
        $this->api = new Api('foo', 'bar');
    }

    /**
     * @dataProvider methodsThatShouldExist
     */
    public function testMethodsExist($method)
    {
        $this->assertMethodExists($method);
    }

    //Custom Assertions

    protected function assertMethodExists($method)
    {
        $this->assertTrue(
            method_exists($this->class, $method)
        );
    }

    protected function assertEndpoint($expected, $id = null)
    {
        $actual = $this->invokeMethod('endpoint', [$id]);

        $this->assertEquals($expected, $actual);
    }

    //Helpers

    protected function invokeMethod($method, array $params)
    {
        $reflection = new \ReflectionClass($this->class::class);
        $method = $reflection->getMethod($method);

        return $method->invokeArgs($this->class, $params);
    }
}