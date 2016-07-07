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

namespace Prooph\ServiceBus\Message\HumusAmqp;

use Humus\Amqp\Producer;
use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\ServiceBus\Exception\RuntimeException;

/**
 * Class PublishConfirmSelectPlugin
 * @package Prooph\ServiceBus\Message\HumusAmqp
 */
final class PublishConfirmSelectPlugin implements Plugin
{
    /**
     * @var Producer
     */
    private $producer;

    /**
     * @var bool
     */
    private $doWait;

    /**
     * @param Producer $producer
     */
    public function __construct(Producer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param EventStore $eventStore
     * @return void
     */
    public function setUp(EventStore $eventStore)
    {
        $eventStore->getActionEventEmitter()->attachListener('commit.post', [$this, 'onEventStoreCommitPostConfirmSelect'], 1000);
        $eventStore->getActionEventEmitter()->attachListener('commit.post', [$this, 'onEventStoreCommitPostWaitForConfirm'], -1000);
    }

    /**
     * @param ActionEvent $actionEvent
     */
    public function onEventStoreCommitPostConfirmSelect(ActionEvent $actionEvent)
    {
        $recordedEvents = $actionEvent->getParam('recordedEvents', new \ArrayIterator());

        if (! $recordedEvents instanceof \Countable) {
            $recordedEvents = iterator_to_array($recordedEvents);
        }

        $countRecordedEvents = count($recordedEvents);

        if (0 === $countRecordedEvents) {
            $this->doWait = false;
            return;
        }

        $this->doWait = true;
        $this->producer->confirmSelect();

        $this->producer->setConfirmCallback(
            function (int $deliveryTag, bool $multiple) use (&$countRecordedEvents) {
                return ($deliveryTag < $countRecordedEvents);
            },
            function (int $deliveryTag, bool $multiple, bool $requeue) use (&$result) {
                throw new RuntimeException('Could not publish all events');
            }
        );
    }

    /**
     * Publish recorded events on the event bus
     *
     * @param ActionEvent $actionEvent
     */
    public function onEventStoreCommitPostWaitForConfirm(ActionEvent $actionEvent)
    {
        if ($this->doWait) {
            $this->producer->waitForConfirm(2);
        }
    }
}
