# Yii2 PusherWS

Pusher WebSocket Real-Time API for Yii2

## What is it?

Pusher Yii2 API is a client library for [Pusher.com](http://pusher.com).
It used Yii2 ReactPHP Event library for create communication stream.

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
}
```

After that run WebSocket
```
$appkey = "YOUR_PUSHER_APP_KEY";

$loop = \React\EventLoop\Factory::create();

$this->socket = new PusherWebSocket( new MyClient( $this ),
    $loop, 'ws.pusherapp.com', 443,
    "/app/$appkey?client=js&version=2.2&protocol=5"
);

$loop->run();

```

## License

MIT, see LICENSE.
