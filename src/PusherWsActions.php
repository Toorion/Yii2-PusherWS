<?php

namespace PusherWS;

/**
 * Class PusherWsActions
 * @package PusherWS
 * Communication actions pack for PusherWS
 * Formatting all message in the Pusher format
 */
trait PusherWsActions
{


    /**
     * Sending Subscribe command
     *
     * @param $channel
     * @param string $auth
     * @param array $channelData
     * @return mixed
     */
    public function subscribe($channel, $auth = '', $channelData = [])
    {
        $request = new \stdClass();
        $request->channel = $channel;
        $request->auth = $auth;
        $request->channel_data = $channelData;

        return $this->sendMessage(self::MSG_SUBSCRIBE, $request);
    }


    /**
     * Sending Unsubscribe command
     *
     * @param $channel
     * @param string $auth
     * @param array $channelData
     * @return mixed
     */
    public function unsubscribe($channel, $auth = '', $channelData = [])
    {
        $request = new \stdClass();
        $request->channel = $channel;
        $request->auth = $auth;
        $request->channel_data = $channelData;

        return $this->sendMessage(self::MSG_UNSUBSCRIBE, $request);
    }



    /**
     * Formatting any message
     *
     * @param $data
     * @param string $type
     * @param bool $masked
     */
    protected function sendMessage($event, $data)
    {
        $request = new \stdClass();
        $request->event = 'pusher:' . $event;
        $request->data = $data;

        $serializedMessage = json_encode($request);

        return $this->send($serializedMessage);
    }


}