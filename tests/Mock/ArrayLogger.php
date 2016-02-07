<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Tests\Mock;

use Psr\Log\LoggerInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ArrayLogger implements LoggerInterface
{
    private $messages = [];

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = [])
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = [])
    {
        $this->log('alert', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = [])
    {
        $this->log('critical', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $this->messages[] = [
            'level'   => $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
