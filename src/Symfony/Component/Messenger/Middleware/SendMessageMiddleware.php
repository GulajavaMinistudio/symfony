<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Middleware;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\Sender\Locator\AbstractSenderLocator;
use Symfony\Component\Messenger\Transport\Sender\Locator\SenderLocatorInterface;

/**
 * @author Samuel Roze <samuel.roze@gmail.com>
 * @author Tobias Schultze <http://tobion.de>
 */
class SendMessageMiddleware implements MiddlewareInterface
{
    private $senderLocator;
    private $messagesToSendAndHandleMapping;

    public function __construct(SenderLocatorInterface $senderLocator, array $messagesToSendAndHandleMapping = array())
    {
        $this->senderLocator = $senderLocator;
        $this->messagesToSendAndHandleMapping = $messagesToSendAndHandleMapping;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if ($envelope->get(ReceivedStamp::class)) {
            // It's a received message. Do not send it back:
            return $stack->next()->handle($envelope, $stack);
        }

        $sender = $this->senderLocator->getSender($envelope);

        if ($sender) {
            $envelope = $sender->send($envelope);

            if (!AbstractSenderLocator::getValueFromMessageRouting($this->messagesToSendAndHandleMapping, $envelope)) {
                // message has no corresponding handler
                return $envelope;
            }
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
