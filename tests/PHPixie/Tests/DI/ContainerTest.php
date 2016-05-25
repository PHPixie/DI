<?php

namespace PHPixie\Tests\DI;

class InstanceStub
{
    public $args;

    public function __construct()
    {
        $this->args = func_get_args();
    }
}

class ContainerStub extends \PHPixie\DI\Container\Root
{
    protected function configure()
    {
        $this->value('a', 5);
        $this->callback('b', function($p) {
            return $p+5;;
        });
        $this->build('c', function() {
            return new \stdClass;
        });
        $this->instance('d', '\PHPixie\Tests\DI\InstanceStub', array(8, 'a', '@a'));

        $this->group('e', function() {
            $this->value('f', 5);
            $this->callback('g', function($p) {
                return $p+5;;
            });
            $this->value('h', new \ArrayObject(array(1, 2, 3)));
        });
    }
}

class NoContainerStub extends ContainerStub
{

}

class ContainerTest extends \PHPixie\Test\Testcase
{
    public function testContainer()
    {
        $container = new ContainerStub();

        $this->assertSame($container, ContainerStub::get());

        $this->assertSame(5, $container->get('a'));
        $this->assertSame(5, $container->a());
        $this->assertSame(5, ContainerStub::a());
        $this->assertSame(5, ContainerStub::get('a'));

        $this->assertTrue(is_callable($container->get('b')));
        $this->assertSame(6, $container->b(1));
        $this->assertSame(6, ContainerStub::b(1));
        $this->assertTrue(is_callable(ContainerStub::get('b')));

        $c = $container->get('c');
        $this->assertInstanceOf('\stdClass', $c);
        $this->assertSame($c, $container->get('c'));
        $this->assertSame($c, $container->c());
        $this->assertSame($c, ContainerStub::c());
        $this->assertSame($c, ContainerStub::get('c'));

        $d = $container->get('d');
        $this->assertSame(array(8, 'a', 5), $d->args);

        $e = $container->get('e');
        $this->assertSame(5, $e->get('f'));
        $this->assertSame(5, $e->f());
        $this->assertSame(5, $container->get('e.f'));
        $this->assertSame(6, $container->call('e.g', array(1)));
        $this->assertSame(6, $e->call('g', array(1)));
        $this->assertSame(3, $container->call('e.h.offsetGet', array(2)));
        $this->assertSame(3, $container->get('e.h.count'));
        $this->assertSame(1, $container->get('e.h.getIterator.current'));

        try{
            $container->get('e.l');
            $this->assertTrue(false);
        } catch(\PHPixie\DI\Exception $e){
            $this->assertSame("'e.l' is not defined", $e->getMessage());
        }
    }

    public function testNoContainer()
    {
        $this->assertException(function() {
            NoContainerStub::get();
        },'\PHPixie\DI\Exception');
    }
}
