<?php

namespace App\Infrastructure\Logs;

use Monolog\Handler\SocketHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
class LogstashLogger {
    /**
     * @param array $config
     * @return LoggerInterface
     */
    public function __invoke(array $config): LoggerInterface
    {
        $handler = new SocketHandler("tcp://{$config['host']}:{$config['port']}");
        $handler->setFormatter(new LogstashFormatter(config('app.name')));

        return new Logger('logstash', [$handler]);
    }
}
