<?php

namespace PusherWS;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;
use PusherWS\FixedStreamEncryption;
use React\SocketClient\ConnectorInterface;

class FixedSecureConnector implements ConnectorInterface
{
    private $connector;
    private $streamEncryption;

    public function __construct(ConnectorInterface $connector, LoopInterface $loop)
    {
        $this->connector = $connector;
        $this->streamEncryption = new FixedStreamEncryption($loop);
    }

    public function create($host, $port)
    {
        return $this->connector->create($host, $port)->then(function (Stream $stream) {
            // (unencrypted) connection succeeded => try to enable encryption
            return $this->streamEncryption->enable($stream)->then(null, function ($error) use ($stream) {
                // establishing encryption failed => close invalid connection and return error
                $stream->close();
                throw $error;
            });
        });
    }
}
