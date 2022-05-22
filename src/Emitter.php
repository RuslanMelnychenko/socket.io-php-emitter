<?php

/**
 * @author Jace Ju <jaceju@gmail.com>
 * @author Soma Szélpál <szelpalsoma@gmail.com>
 * @author Anton Pavlov <anton.pavlov.it@gmail.com>
 * @license MIT
 */

namespace Goez\SocketIO;

use MessagePack\Packer;

/**
 * Class Emitter
 * @package Goez\SocketIO
 * @property-read Emitter $json
 * @property-read Emitter $volatile
 * @property-read Emitter $broadcast
 */
class Emitter
{
    /**
     * @var int
     */
    const EVENT_TYPE_REGULAR = 2;

    /**
     * @var int
     */
    const EVENT_TYPE_BINARY = 5;

    /**
     * @var int
     */
    const REQUEST_REMOTE_JOIN = 2;

    /**
     * @var int
     */
    const REQUEST_REMOTE_LEAVE = 3;

    /**
     * @var int
     */
    const REQUEST_REMOTE_DISCONNECT = 4;

    /**
     * @var int
     */
    const REQUEST_SERVER_SIDE_EMIT = 6;

    /**
     * @var
     */
    const FLAG_JSON = 'json';

    /**
     * @var string
     */
    const FLAG_VOLATILE = 'volatile';

    /**
     * @var string
     */
    const FLAG_BROADCAST = 'broadcast';

    /**
     * Default namespace
     *
     * @var string
     */
    const DEFAULT_NAMESPACE = '/';

    /**
     * @var string
     */
    protected $uid = 'emitter';

    /**
     * @var int
     */
    protected $type;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * Rooms
     * @var array
     */
    protected $rooms;

    /**
     * Except rooms
     * @var array
     */
    protected $exceptRooms;

    /**
     * @var array
     */
    protected $validFlags = [];

    /**
     * @var array
     */
    protected $flags;

    /**
     * @var Packer
     */
    protected $packer;

    /**
     * @var \Redis
     */
    protected $client;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * Emitter constructor.
     *
     * @param \Redis $client
     * @param string $prefix
     * @throws \InvalidArgumentException
     */
    public function __construct(\Redis $client, $prefix = 'socket.io')
    {
        $this->client = $client;
        $this->prefix = $prefix;
        $this->packer = new Packer();
        $this->reset();

        $this->validFlags = [
            self::FLAG_JSON,
            self::FLAG_VOLATILE,
            self::FLAG_BROADCAST,
        ];
    }

    /**
     * Set room
     *
     * @param  string|array $room
     * @return $this
     */
    public function in($room)
    {
        //multiple
        if (is_array($room)) {
            foreach ($room as $r) {
                $this->in($r);
            }
            return $this;
        }
        //single
        if (!in_array($room, $this->rooms, true)) {
            $this->rooms[] = $room;
        }
        return $this;
    }

    /**
     * Alias for in
     *
     * @param  string $room
     * @return $this
     */
    public function to($room)
    {
        return $this->in($room);
    }

    /**
     *
     * Except rooms
     *
     * @param string|string[] $room
     * @return $this
     */
    public function except($room): self
    {
        //multiple
        if (is_array($room)) {
            foreach ($room as $r) {
                $this->except($r);
            }
            return $this;
        }
        //single
        if (!in_array($room, $this->exceptRooms, true)) {
            $this->exceptRooms[] = $room;
        }
        return $this;
    }

    /**
     * Set a namespace
     *
     * @param  string $namespace
     * @return $this
     */
    public function of($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Set flags with magic method
     *
     * @param  string $flag
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function __get($flag)
    {
        return $this->flag($flag);
    }

    /**
     * Set flags
     *
     * @param  string $flag
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function flag($flag = null)
    {
        if (!in_array($flag, $this->validFlags, true)) {
            throw new \InvalidArgumentException('Invalid socket.io flag used: ' . $flag);
        }

        $this->flags[$flag] = true;

        return $this;
    }

    /**
     * Set type
     *
     * @param  int $type
     * @return $this
     */
    public function type($type = self::EVENT_TYPE_REGULAR)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Emitting
     *
     * @return $this
     */
    public function emit()
    {
        $packet = [
            'type' => $this->type,
            'data' => func_get_args(),
            'nsp' => $this->namespace,
        ];

        $options = [
            'rooms' => $this->rooms,
            'except' => $this->exceptRooms,
            'flags' => $this->flags,
        ];
        $channelName = sprintf('%s#%s#', $this->prefix, $packet['nsp']);

        $message = $this->packer->pack([$this->uid, $packet, $options]);

        // hack buffer extensions for msgpack with binary
        if ($this->type === self::EVENT_TYPE_BINARY) {
            $message = str_replace([
                pack('c', 0xda),
                pack('c', 0xdb)
            ], [
                pack('c', 0xd8),
                pack('c', 0xd9)
            ], $message);
        }

        // publish
        if (is_array($this->rooms) && count($this->rooms) > 0) {
            foreach ($this->rooms as $room) {
                $chnRoom = $channelName . $room . '#';
                $this->client->publish($chnRoom, $message);
            }
        } else {
            $this->client->publish($channelName, $message);
        }

        // reset state
        return $this->reset();
    }

    protected function emitToServer(array $request)
    {
        $channelName = sprintf('%s-request#%s#', $this->prefix, $this->namespace);
        $request = json_encode($request);
        $this->client->publish($channelName, $request);
    }

    /**
     * Send a packet to the Socket.IO servers in the cluster
     *
     * @param string $event
     * @param scalar|array|object ...$args
     * @return void
     */
    public function serverSideEmit(string $event, ...$args)
    {
        $message = [
            'uid' => $this->uid,
            'type' => self::REQUEST_SERVER_SIDE_EMIT,
            'data' => array_merge([$event], $args)
        ];
        $this->emitToServer($message);
    }

    /**
     * Makes the matching socket instances join the specified rooms
     *
     * @param string|string[] $rooms
     * @return $this
     */
    public function socketsJoin($rooms): self
    {
        $message = [
            'type' => self::REQUEST_REMOTE_JOIN,
            'opts' => [
                'rooms' => $this->rooms,
                'except' => $this->exceptRooms,
            ],
            'rooms' => is_array($rooms) ? $rooms : [$rooms],
        ];
        $this->emitToServer($message);
        return $this;
    }

    /**
     * Makes the matching socket instances leave the specified rooms
     *
     * @param string|string[] $rooms
     * @return $this
     */
    public function socketsLeave($rooms): self
    {
        $message = [
            'type' => self::REQUEST_REMOTE_LEAVE,
            'opts' => [
                'rooms' => $this->rooms,
                'except' => $this->exceptRooms,
            ],
            'rooms' => is_array($rooms) ? $rooms : [$rooms],
        ];
        $this->emitToServer($message);
        return $this;
    }

    /**
     * Makes the matching socket instances disconnect
     *
     * @param bool $close whether to close the underlying connection
     * @return $this
     */
    public function disconnectSockets(bool $close = false): self
    {
        $message = [
            'type' => self::REQUEST_REMOTE_DISCONNECT,
            'opts' => [
                'rooms' => $this->rooms,
                'except' => $this->exceptRooms,
            ],
            'close' => $close,
        ];
        $this->emitToServer($message);
        return $this;
    }

    /**
     * Reset all values
     * @return $this
     */
    protected function reset()
    {
        $this->rooms = [];
        $this->exceptRooms = [];
        $this->flags = [];
        $this->namespace = self::DEFAULT_NAMESPACE;
        $this->type = self::EVENT_TYPE_REGULAR;
        return $this;
    }
}
