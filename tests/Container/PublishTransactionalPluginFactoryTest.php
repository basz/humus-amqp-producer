<?php
/*
 * This file is part of the prooph/humus-amqp-producer.
 * (c) 2016 prooph software GmbH <contact@prooph.de>
 * (c) 2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);

namespace ProophTest\ServiceBus\Message\HumusAmqp\Container;

use Humus\Amqp\Producer;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\ServiceBus\Message\HumusAmqp\Container\PublishTransactionalPluginFactory;
use Prooph\ServiceBus\Message\HumusAmqp\PublishTransactionalPlugin;

/**
 * Class PublishTransactionalPluginFactoryTest
 * @package ProophTest\ServiceBus\Message\HumusAmqp\Container
 */
class PublishTransactionalPluginFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_plugin()
    {
        $producer = $this->prophesize(Producer::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('producer_name')->willReturn($producer->reveal());

        $factory = new PublishTransactionalPluginFactory('producer_name');
        $plugin = $factory($container->reveal());

        $this->assertInstanceOf(PublishTransactionalPlugin::class, $plugin);
    }

    /**
     * @test
     */
    public function it_creates_plugin_via_call_static()
    {
        $producer = $this->prophesize(Producer::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('producer_name')->willReturn($producer->reveal());

        $producerName = 'producer_name';

        $plugin = PublishTransactionalPluginFactory::$producerName($container->reveal());

        $this->assertInstanceOf(PublishTransactionalPlugin::class, $plugin);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_container_given()
    {
        $this->expectException(\Prooph\ServiceBus\Exception\InvalidArgumentException::class);

        $producerName = 'producer_name';

        PublishTransactionalPluginFactory::$producerName('invalid');
    }
}
