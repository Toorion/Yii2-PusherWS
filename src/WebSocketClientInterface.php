<?php

namespace PusherWS;

interface WebSocketClientInterface
{
    /**
     * @param array $data
     */
    function onWelcome(array $data);

    /**
     * @param string $subscriptionId
     * @param string $publicationId
     * @param string $details
     * @param string $data
     */
    public function onMessage(array $data );

    /**
     * @param WebSocketClient $client
     */
    function setClient(PusherWebSocket $client);
}
