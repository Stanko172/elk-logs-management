<?php

namespace App\Infrastructure\Logs;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

class LogstashFormatter extends NormalizerFormatter
{
    /**
     * @var string the name of the system for the Logstash log message, used to fill the @source field
     */
    protected string $systemName;

    /**
     * @var string an application name for the Logstash log message, used to fill the @type field
     */
    protected string $applicationName;

    /**
     * @var string the key for 'extra' fields from the Monolog record
     */
    protected string $extraKey;

    /**
     * @var string the key for 'context' fields from the Monolog record
     */
    protected string $contextKey;

    /**
     * @param string      $applicationName The application that sends the data, used as the "type" field of logstash
     * @param string|null $systemName      The system/machine name, used as the "source" field of logstash, defaults to the hostname of the machine
     * @param string      $extraKey        The key for extra keys inside logstash "fields", defaults to extra
     * @param string      $contextKey      The key for context keys inside logstash "fields", defaults to context
     *
     * @throws \RuntimeException If the function json_encode does not exist
     */
    public function __construct(string $applicationName, ?string $systemName = null, string $extraKey = 'extra', string $contextKey = 'context')
    {
        // logstash requires a ISO 8601 format date with optional millisecond precision.
        parent::__construct('Y-m-d\TH:i:s.uP');

        $this->systemName = $systemName === null ? (string) gethostname() : $systemName;
        $this->applicationName = $applicationName;
        $this->extraKey = $extraKey;
        $this->contextKey = $contextKey;
    }

    /**
     * @inheritDoc
     */
    public function format(LogRecord $record): string
    {
        $recordData = parent::format($record);

        $message = [
            '@timestamp' => $recordData['datetime'],
            '@version' => 1,
            'host' => $this->systemName,
        ];

        if (isset($recordData['message'])) {
            $message['message'] = $recordData['message'];
        }

        if (isset($recordData['channel'])) {
            $message['type'] = $recordData['channel'];
            $message['channel'] = $recordData['channel'];
        }

        if (isset($recordData['level_name'])) {
            $message['log_level'] = $recordData['level_name'];
        }

        if ('' !== $this->applicationName) {
            $message['type'] = $this->applicationName;
        }

        if (count($recordData['context']) > 0) {
            $message[$this->contextKey] = $recordData['context'];
        }

        return $this->toJson($message);
    }
}
