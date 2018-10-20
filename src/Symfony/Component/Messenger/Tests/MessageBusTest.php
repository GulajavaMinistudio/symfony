<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\EnvelopeAwareInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Tests\Fixtures\AnEnvelopeStamp;
use Symfony\Component\Messenger\Tests\Fixtures\DummyMessage;

class MessageBusTest extends TestCase
{
    public function testItHasTheRightInterface()
    {
        $bus = new MessageBus();

        $this->assertInstanceOf(MessageBusInterface::class, $bus);
    }

    /**
     * @expectedException \Symfony\Component\Messenger\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid type for message argument. Expected object, but got "string".
     */
    public function testItDispatchInvalidMessageType()
    {
        (new MessageBus())->dispatch('wrong');
    }

    public function testItCallsMiddlewareAndChainTheReturnValue()
    {
        $message = new DummyMessage('Hello');
        $responseFromDepthMiddleware = 1234;

        $firstMiddleware = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $firstMiddleware->expects($this->once())
            ->method('handle')
            ->with($message, $this->anything())
            ->will($this->returnCallback(function ($message, $next) {
                return $next($message);
            }));

        $secondMiddleware = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $secondMiddleware->expects($this->once())
            ->method('handle')
            ->with($message, $this->anything())
            ->willReturn($responseFromDepthMiddleware);

        $bus = new MessageBus(array(
            $firstMiddleware,
            $secondMiddleware,
        ));

        $this->assertEquals($responseFromDepthMiddleware, $bus->dispatch($message));
    }

    public function testItKeepsTheEnvelopeEvenThroughAMiddlewareThatIsNotEnvelopeAware()
    {
        $message = new DummyMessage('Hello');
        $envelope = new Envelope($message, new ReceivedStamp());

        $firstMiddleware = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $firstMiddleware->expects($this->once())
            ->method('handle')
            ->with($message, $this->anything())
            ->will($this->returnCallback(function ($message, $next) {
                return $next($message);
            }));

        $secondMiddleware = $this->getMockBuilder(array(MiddlewareInterface::class, EnvelopeAwareInterface::class))->getMock();
        $secondMiddleware->expects($this->once())
            ->method('handle')
            ->with($envelope, $this->anything())
        ;

        $bus = new MessageBus(array(
            $firstMiddleware,
            $secondMiddleware,
        ));

        $bus->dispatch($envelope);
    }

    public function testThatAMiddlewareCanAddSomeStampsToTheEnvelope()
    {
        $message = new DummyMessage('Hello');
        $envelope = new Envelope($message, new ReceivedStamp());
        $envelopeWithAnotherStamp = $envelope->with(new AnEnvelopeStamp());

        $firstMiddleware = $this->getMockBuilder(array(MiddlewareInterface::class, EnvelopeAwareInterface::class))->getMock();
        $firstMiddleware->expects($this->once())
            ->method('handle')
            ->with($envelope, $this->anything())
            ->will($this->returnCallback(function ($message, $next) {
                return $next($message->with(new AnEnvelopeStamp()));
            }));

        $secondMiddleware = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $secondMiddleware->expects($this->once())
            ->method('handle')
            ->with($message, $this->anything())
            ->will($this->returnCallback(function ($message, $next) {
                return $next($message);
            }));

        $thirdMiddleware = $this->getMockBuilder(array(MiddlewareInterface::class, EnvelopeAwareInterface::class))->getMock();
        $thirdMiddleware->expects($this->once())
            ->method('handle')
            ->with($envelopeWithAnotherStamp, $this->anything())
        ;

        $bus = new MessageBus(array(
            $firstMiddleware,
            $secondMiddleware,
            $thirdMiddleware,
        ));

        $bus->dispatch($envelope);
    }

    public function testThatAMiddlewareCanUpdateTheMessageWhileKeepingTheEnvelopeStamps()
    {
        $message = new DummyMessage('Hello');
        $envelope = new Envelope($message, ...$stamps = array(new ReceivedStamp()));

        $changedMessage = new DummyMessage('Changed');
        $expectedEnvelope = new Envelope($changedMessage, ...$stamps);

        $firstMiddleware = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $firstMiddleware->expects($this->once())
            ->method('handle')
            ->with($message, $this->anything())
            ->will($this->returnCallback(function ($message, $next) use ($changedMessage) {
                return $next($changedMessage);
            }));

        $secondMiddleware = $this->getMockBuilder(array(MiddlewareInterface::class, EnvelopeAwareInterface::class))->getMock();
        $secondMiddleware->expects($this->once())
            ->method('handle')
            ->with($expectedEnvelope, $this->anything())
        ;

        $bus = new MessageBus(array(
            $firstMiddleware,
            $secondMiddleware,
        ));

        $bus->dispatch($envelope);
    }
}
