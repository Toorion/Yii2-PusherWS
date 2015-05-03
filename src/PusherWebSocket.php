<?php

namespace PusherWS;

use React\Dns\Resolver\Factory as DNSFactory;
use React\SocketClient\SecureStream;
use React\EventLoop\StreamSelectLoop;

use React\SocketClient\Connector;
use PusherWS\FixedSecureConnector; // Fixed bug with newest version of PHP

use Ratchet\WebSocket\Version\RFC6455\Message;
use Ratchet\WebSocket\Version\RFC6455\Frame;

class PusherWebSocket
{
    /**
     * Low level stream tools
     */
    use PusherWsServant;

    /**
     * High level communication actions
     */
    use PusherWsActions;

    /**
     * WebSocket client version
     */
    const VERSION = '0.1.4';

    /**
     * Session unique token length
     */
    const TOKEN_LENGHT = 16;

    /**
     * Message code
     * @const string
     */
    const MSG_SUBSCRIBE = 'subscribe';
    const MSG_UNSUBSCRIBE = 'unsubscribe';

    /**
     * DNS server IP for name resolver
     * @var string
     */
    public $dnsServer = '8.8.8.8';

    /**
     * @var StreamSelectLoop
     */
    protected $loop;

    /**
     * WebSocket server host name / IP
     * @var string
     */
    protected $host;

    /**
     * Connection port (443 for secure connection)
     * @var int
     */
    protected $port;

    /**
     * Connection path
     * @var string
     */
    protected $path;

    /**
     * Connection client
     * @var WebSocketClientInterface
     */
    protected $client;

    /**
     * Connection origin
     * @var string
     */
    protected $origin;

    /**
     * Unique connection token (generated)
     * @var string
     */
    protected $key;

    /**
     * Connection status
     * @var bool
     */
    protected $connected = false;

    /**
     * Active stream
     * @var Stream | SecureStream
     */
    protected $stream;

    /**
     * Communication message
     * @var Message
     */
    protected $_message;

    /**
     * Part of communication message
     * @var Frame
     */
    protected $_frame;


    /**
     * @param WebSocketClientInterface $client
     * @param StreamSelectLoop $loop
     * @param string $host
     * @param int $port
     * @param string $path
     * @param null|string $origin
     */
    function __construct(WebSocketClientInterface $client, StreamSelectLoop $loop, $host = '127.0.0.1', $port = 8080, $path = '/', $origin = null)
    {
        $this->loop = $loop;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->client = $client;
        $this->origin = $origin;

        $this->key = $this->generateToken(self::TOKEN_LENGHT);

        $this->connect();

        $client->setClient($this);
    }


    /**
     * Connect client to server
     *
     * @return $this
     */
    public function connect()
    {
        $root = $this;

        $dnsResolverFactory = new DNSFactory();
        $dns = $dnsResolverFactory->createCached($this->dnsServer, $this->loop);

        $connector = new Connector($this->loop, $dns);

        if (443 == $this->port) {
            $connector = new FixedSecureConnector($connector, $this->loop);
        }

        $connector->create($this->host, $this->port)->then(function ($stream) use ($root) {
            $root->stream = $stream;

            $stream->write($root->createHeader());

            $stream->on('data', function ($data) use ($root) {
                $data = $root->parseIncomingRaw($data);
                $root->parseData($data);
            });
        });

        return $this;
    }


    /**
     * Parse received data
     */
    protected function parseData($response)
    {
        if (!$this->connected && isset($response['Sec-Websocket-Accept'])) {
            if (base64_encode(pack('H*', sha1($this->key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'))) === $response['Sec-Websocket-Accept']) {
                $this->connected = true;
            }
        }
        if ($this->connected && !empty($response['content'])) {
            $this->handleData(trim($response['content']));
        }
    }


    /**
     * Convert Raw Data to Message
     *
     * @param string $data
     */
    protected function handleData($data)
    {
        if (0 === strlen($data)) {
            return;
        }

        if (!$this->_message) {
            $this->_message = new Message;
        }
        if (!$this->_frame) {
            $frame = new Frame();
        } else {
            $frame = $this->_frame;
        }

        $frame->addBuffer($data);

        if ($frame->isCoalesced()) {
            $opcode = $frame->getOpcode();

            if ($opcode > 2) {
                if ($frame->getPayloadLength() > 125 || !$frame->isFinal()) {
                    $this->processMessage($frame->getPayload());
                    return;
                }

                switch ($opcode) {
                    case Frame::OP_CLOSE:
                        $this->close($frame->getPayload());
                        return;
                    case Frame::OP_PING:
                        $this->send(new Frame($frame->getPayload(), true, Frame::OP_PONG));
                        break;
                        break;
                    default:
                        $this->processMessage($frame->getPayload());
                        return;
                }
            }

            $overflow = $frame->extractOverflow();

            $this->_frame = null;

            // if this is a control frame, then we aren't going to be coalescing
            // any message, just handle overflowing stuff now and return
            if ($opcode > 2) {
                $this->handleData($overflow);
                return;
            } else {
                $this->_message->addFrame($frame);
            }
        } else {
            $this->_frame = $frame;
        }

        if (!$this->_message->isCoalesced()) {
            if (isset($overflow)) {
                $this->handleData($overflow);
            }

            return;
        }

        $message = $this->_message->getPayload();
        $this->_frame = $this->_message = null;

        $this->processMessage($message);

        $this->handleData($overflow);
    }


    /**
     * Execute action depended of Message
     *
     * @param $content
     * @return bool
     */
    protected function processMessage($content)
    {
        $data = json_decode($content, true);

        if (!isset($data['event']))
            return false;

        $event = $data['event'];
        $data = $data['data'];

        switch ($event) {
            case 'pusher:connection_established':
                $this->client->onWelcome(json_decode($data, true));
                break;
            case 'message':
                $this->client->onMessage(json_decode($data, true));
                break;
            case 'pusher:ping':
                $this->sendMessage('pong', new \stdClass());
                break;
        }
    }


    /**
     * Closing active connection with CLOSE signal
     *
     * @param int $code
     */
    public function close($code = 1000)
    {
        $frame = new Frame(pack('n', $code), true, Frame::OP_CLOSE);

        $this->stream->write($frame->getContents());
        $this->stream->end();
    }


    /**
     * Sending Frame of Message to the stream
     *
     * @param $msg
     * @return bool|void
     */
    public function send($msg)
    {
        if ($msg instanceof Frame) {
            $frame = $msg;
        } else {
            $frame = new Frame($msg);
        }
        $frame->maskPayload($frame->generateMaskingKey());

        return $this->stream->write($frame->getContents());
    }

}
