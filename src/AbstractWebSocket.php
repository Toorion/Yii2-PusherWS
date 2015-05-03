<?php

namespace PusherWS;;

use PusherWS\WebSocketClientInterface;


abstract class AbstractWebSocket implements WebSocketClientInterface
{
    /**
     * @var PusherWebSocket
     */
    protected $client;

    /**
     * Subscriptions should be here
     *
     * @param array $data
     * @return mixed
     */
    abstract function onWelcome(array $data);

    public function onMessage( array $data )
    {
        echo "Message received\n";
        var_dump( $data );
        echo "\n";
    }

    /**
     * Subscibe to channel
     *
     * @param $topic
     */
    public function subscribe($topic)
    {
        $this->client->subscribe($topic);
    }

    /**
     * Unsubscribe from channel
     *
     * @param $topic
     */
    public function unsubscribe($topic)
    {
        $this->client->unsubscribe($topic);
    }

    public function setClient(PusherWebSocket $client)
    {
        $this->client = $client;
    }

}