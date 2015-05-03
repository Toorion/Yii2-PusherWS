# Yii2 PusherWS

Yii2 WebSocket Real-Time API for Pusher

## What is it?

Pusher Yii2 API is a client library for [Pusher.com](http://pusher.com).
It used Yii2 ReactPHP Event library for create communication stream.

## Usage

Create your application client.
```php
class MyClient extends AbstractWebSocket
{
    public function onWelcome(array $data)
    {
        $this->subscribe("channel_you_needed");
        echo "SUBSCRIBE COMMAND SEND\n";
    }

    public function onMessage( array $data )
    {
        return parent::onMessage( $data );
        var_dump($data);
    }
}
```

After that run WebSocket
```php
$appkey = "YOUR_PUSHER_APP_KEY";

$loop = \React\EventLoop\Factory::create();

$this->socket = new PusherWebSocket( new MyClient(), $loop
    'ws.pusherapp.com', 443, "/app/$appkey?client=js&version=2.2&protocol=5"
);

$loop->run();

```

## License

MIT, see LICENSE.
