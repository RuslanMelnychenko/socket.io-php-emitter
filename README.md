# ruslanmelnychenko/socket.io-php-emitter [![Build Status](https://travis-ci.org/ruslanmelnychenko/socket.io-php-emitter.svg?branch=master)](https://travis-ci.org/ruslanmelnychenko/socket.io-php-emitter)

A PHP implementation of node.js socket.io-emitter.
Use PhpRedis client.

## Installation

```bash
composer require ruslanmelnychenko/socket.io-php-emitter
```

## Usage

### Emit payload message
```php
use RuslanMelnychenko\SocketIO\Emitter;
...

$client = new \Redis();

(new Emitter($client))
    ->of('namespace')->emit('event', 'payload message');
```

### Flags
Possible flags
* json
* volatile
* broadcast

#### To use flags, just call it like in examples bellow
```php
use RuslanMelnychenko\SocketIO\Emitter;
...

$client = new \Redis();
$client->connect('127.0.0.1');

(new Emitter($client))
    ->broadcast->emit('broadcast-event', 'payload message');

(new Emitter($client))
    ->flag('broadcast')->emit('broadcast-event', 'payload message');
```

### Emit an object
```php
use RuslanMelnychenko\SocketIO\Emitter;
...

$client = new \Redis();
$client->connect('127.0.0.1');

(new Emitter($client))
    ->emit('broadcast-event', ['param1' => 'value1', 'param2' => 'value2', ]);
```

### Emit an object in multiple rooms
```php
use RuslanMelnychenko\SocketIO\Emitter;
...

$client = new \Redis();
$client->connect('127.0.0.1');

(new Emitter($client))
    ->in(['room1', 'room2'])
    ->emit('broadcast-event', ['param1' => 'value1', 'param2' => 'value2', ]);
```

### Emit an object in multiple rooms with except rooms
```php
use RuslanMelnychenko\SocketIO\Emitter;
...

$client = new \Redis();
$client->connect('127.0.0.1');

(new Emitter($client))
    ->in(['room1', 'room2'])
    ->except(['room3'])
    ->emit('broadcast-event', ['param1' => 'value1', 'param2' => 'value2', ]);
```

### Send remote commands
```php
use RuslanMelnychenko\SocketIO\Emitter;
...

$client = new \Redis();
$client->connect('127.0.0.1');

(new Emitter($client))
    ->in(['room1', 'room2'])
    ->except(['room3'])
    ->socketsJoin('room4');
    
(new Emitter($client))
    ->in(['room4'])
    ->socketsLeave('room3');
    
(new Emitter($client))
    ->in(['room1'])
    ->disconnectSockets();

(new Emitter($client))
    ->of('/tasks')
    ->serverSideEmit('taskDone', 1);
```

## Credits

[Watch all forks](https://github.com/RuslanMelnychenko/socket.io-php-emitter/network/members)
