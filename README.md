<p align="center">
    <a href="https://roadrunner.dev"><picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://github.com/roadrunner-server/.github/assets/8040338/e6bde856-4ec6-4a52-bd5b-bfe78736c1ff">
        <img alt="RoadRunner" src="https://github.com/roadrunner-server/.github/assets/8040338/040fb694-1dd3-4865-9d29-8e0748c2c8b8" style="width: 6in; display: block">
    </picture></a>
</p>

<p align="center">The common PHP worker for the RoadRunner application server</p>

<div align="center">

[![Documentation](https://img.shields.io/badge/Documentation-blue?style=for-the-badge&logo=gitbook&logoColor=white)](https://docs.roadrunner.dev/docs/php-worker/worker)
[![Sponsor](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsor&logo=githubsponsors&logoColor=white&color=%23EA4AAA)](https://github.com/sponsors/roadrunner-server)

[![Psalm Level](https://shepherd.dev/github/roadrunner-php/worker/level.svg)](https://shepherd.dev/github/roadrunner-php/worker)
[![Type Coverage](https://shepherd.dev/github/roadrunner-php/worker/coverage.svg)](https://shepherd.dev/github/roadrunner-php/worker)
[![Mutation testing badge](https://img.shields.io/endpoint?url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Froadrunner-php%2Fworker%2F3.x)](https://dashboard.stryker-mutator.io/reports/github.com/roadrunner-php/worker/3.x)

</div>

<br />

This package contains the common codebase for all RoadRunner PHP workers: it receives payloads from the server over [Goridge](https://github.com/roadrunner-php/goridge), sends responses back and gives access to the worker environment and the worker pool.
Check [roadrunner-server/roadrunner](https://github.com/roadrunner-server/roadrunner) for the application server itself and [roadrunner-php/http](https://github.com/roadrunner-php/http) for a PSR-7 compatible HTTP worker.

## Get Started

### Installation

```bash
composer require spiral/roadrunner-worker
```

[![PHP](https://img.shields.io/packagist/php-v/spiral/roadrunner-worker.svg?style=flat-square&logo=php)](https://packagist.org/packages/spiral/roadrunner-worker)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/spiral/roadrunner-worker.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/spiral/roadrunner-worker)
[![License](https://img.shields.io/packagist/l/spiral/roadrunner-worker.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/spiral/roadrunner-worker.svg?style=flat-square)](https://packagist.org/packages/spiral/roadrunner-worker/stats)

The RoadRunner binary can be downloaded with the [RoadRunner CLI](https://github.com/roadrunner-php/cli):

```bash
composer require spiral/roadrunner-cli --dev
vendor/bin/rr get
```

### Configuration

Point the RoadRunner server to your worker script in `.rr.yaml`:

```yaml
version: '3'

server:
  command: "php worker.php"
```

See the [documentation](https://docs.roadrunner.dev/docs/php-worker/worker) for the full list of options.

### Writing a Worker

A minimal worker that receives payloads and responds to them:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\Worker;

// Create a new Worker from the global environment
$worker = Worker::create();

while ($payload = $worker->waitPayload()) {
    // Received payload
    var_dump($payload->body);

    // Respond
    $worker->respond(new Payload('DONE'));
}
```

## Testing

```bash
composer test
```

<a href="https://spiral.dev/">
<img src="https://user-images.githubusercontent.com/773481/220979012-e67b74b5-3db1-41b7-bdb0-8a042587dedc.jpg" alt="try Spiral Framework" />
</a>
