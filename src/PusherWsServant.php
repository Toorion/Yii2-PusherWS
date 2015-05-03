<?php

namespace PusherWS;


/**
 * Class PusherWsServant
 * @package PusherWS
 * Lov level stream functions
 */
trait PusherWsServant
{


    /**
     * Generate token
     *
     * @param int $length
     * @return string
     */
    protected function generateToken($length)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';

        $useChars = array();

        // select some random chars:
        for ($i = 0; $i < $length; $i++) {
            $useChars[] = $characters[mt_rand(0, strlen($characters) - 1)];
        }

        // Add numbers
        array_push($useChars, rand(0, 9), rand(0, 9), rand(0, 9));
        shuffle($useChars);
        $randomString = trim(implode('', $useChars));
        $randomString = substr($randomString, 0, self::TOKEN_LENGHT);

        return base64_encode($randomString);
    }


    /**
     * Create header for websocket client
     *
     * @return string
     */
    protected function createHeader()
    {
        if ($this->host === '127.0.0.1' || $this->host === '0.0.0.0') {
            $this->host = 'localhost';
        }

        $origin = $this->origin ? $this->origin : "null";

        return
            "GET {$this->path} HTTP/1.1" . "\r\n" .
            "Origin: {$origin}" . "\r\n" .
            "Host: {$this->host}:{$this->port}" . "\r\n" .
            "Sec-WebSocket-Key: {$this->key}" . "\r\n" .
            "User-Agent: PHPWebSocketClient/" . self::VERSION . "\r\n" .
            "Upgrade: websocket" . "\r\n" .
            "Connection: Upgrade" . "\r\n" .
            "Sec-WebSocket-Protocol: wamp.2.json" . "\r\n" .
            "Sec-WebSocket-Version: 13" . "\r\n" .
            "\r\n";
    }


    /**
     * Parse raw incoming data
     *
     * @param $header
     * @return array
     */
    protected function parseIncomingRaw($header)
    {
        $retval = array();
        $content = "";
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach ($fields as $field) {
            if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
                $match[1] = preg_replace_callback('/(?<=^|[\x09\x20\x2D])./', function ($matches) {
                    return strtoupper($matches[0]);
                }, strtolower(trim($match[1])));
                if (isset($retval[$match[1]])) {
                    $retval[$match[1]] = array($retval[$match[1]], $match[2]);
                } else {
                    $retval[$match[1]] = trim($match[2]);
                }
            } else if (preg_match('!HTTP/1\.\d (\d)* .!', $field)) {
                $retval["status"] = $field;
            } else {
                $content .= $field . "\r\n";
            }
        }
        $retval['content'] = $content;

        return $retval;
    }


}