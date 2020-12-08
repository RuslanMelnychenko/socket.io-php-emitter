# goez/socket.io-php-emitter [![Build Status](https://travis-ci.org/goez-tools/socket.io-php-emitter.svg?branch=master)](https://travis-ci.org/goez-tools/socket.io-php-emitter)

A PHP implementation of node.js socket.io-emitter.

## Installation

```bash
composer require fantik11/socket.io-php-emitter
```

## Usage

### Emit payload message
```php
use Predis\Client;
use Goez\SocketIO\Emitter;
...

$client = new Client();

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
use Predis\Client;
use Goez\SocketIO\Emitter;
...

$client = new Client();

(new Emitter($client))
    ->broadcast->emit('broadcast-event', 'payload message');

(new Emitter($client))
    ->flag('broadcast')->emit('broadcast-event', 'payload message');
```

### Emit an object
```php
use Predis\Client;
use Goez\SocketIO\Emitter;
...

$client = new Client();

(new Emitter($client))
    ->emit('broadcast-event', ['param1' => 'value1', 'param2' => 'value2', ]);
```

### Emit an object in multiple rooms
```php
use Predis\Client;
use Goez\SocketIO\Emitter;
...

$client = new Client();

(new Emitter($client))
    ->in(['room1', 'room2'])
    ->emit('broadcast-event', ['param1' => 'value1', 'param2' => 'value2', ]);
```

## Credits

This library is forked from [shakahl/socket.io-emitter](https://github.com/shakahl/socket.io-emitter) created by Soma Szélpál.

The [shakahl/socket.io-emitter](https://github.com/shakahl/socket.io-emitter) is forked from [exls/socket.io-emitter](https://github.com/exls/socket.io-emitter) created by Anton Pavlov.

