# Yii2 PusherWS

Pusher WebSocket Real Time API for Yii2

## What is it?

Pusher Yii2 API is a client library for [Pusher.com](http://pusher.com).
It used Yii2 ReactPHP Event library for create communication stream.

React is a low-level library for event-driven programming in PHP. At its core
is an event loop, on top of which it  provides low-level utilities, such as:
Streams abstraction, async dns resolver, network client/server, http
client/server, interaction with processes. Third-party libraries can use these
components to create async network clients/servers and more.

The event loop is based on the reactor pattern (hence the name) and strongly
inspired by libraries such as EventMachine (Ruby), Twisted (Python) and
Node.js (V8).

## Usage

Create your application client.
```php
<?php

class MyClient extends AbstractWebSocket
{
    public function onWelcome(array $data)
    {
        echo "WELCOME\n";

        $this->subscribe("ticker.3");

        echo "SUBSCRIBE SEND\n";
    }


    public function onMessage( array $data )
    {
        return parent::onMessage( $data );
        var_dump($data);

    }

}```

After that run WebSocket
```
$appkey = "YOUR_PUSHER_APP_KEY";

$loop = \React\EventLoop\Factory::create();

$this->socket = new PusherWebSocket( new MyClient( $this ), $loop, 'ws.pusherapp.com', 443, "/app/$appkey?client=js&version=2.2&protocol=5");

$loop->run();

```

## License

MIT, see LICENSE.
