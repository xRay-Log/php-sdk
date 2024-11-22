<?php

namespace XRayLog;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class XRayLogger
{
    public const API_URL = 'http://localhost:44827';

    private $project;
    private $client;
    private $cloner;
    private $dumper;

    public function __construct(string $project, ?ClientInterface $client = null)
    {
        $this->project = $project;
        $this->client = $client ?? new Client();
        $this->cloner = new VarCloner();
    }

    public function setProject(string $project): void
    {
        $this->project = $project;
    }

    /**
     * Log an info message
     *
     * @param mixed $payload Data to log
     * @return bool
     * @throws \RuntimeException
     */
    public function info($payload): bool
    {
        return $this->log('info', $payload);
    }

    /**
     * Log an error message
     *
     * @param mixed $payload Data to log
     * @return bool
     * @throws \RuntimeException
     */
    public function error($payload): bool
    {
        return $this->log('error', $payload);
    }

    /**
     * Log a warning message
     *
     * @param mixed $payload Data to log
     * @return bool
     * @throws \RuntimeException
     */
    public function warning($payload): bool
    {
        return $this->log('warning', $payload);
    }

    /**
     * Log a debug message
     *
     * @param mixed $payload Data to log
     * @return bool
     * @throws \RuntimeException
     */
    public function debug($payload): bool
    {
        return $this->log('debug', $payload);
    }

    /**
     * Convert variable to HTML representation
     *
     * @param mixed $var Variable to convert
     * @return string HTML representation
     */
    private function convertToHtml($var): string
    {
        $this->dumper = new HtmlDumper();
        ob_start();
        $this->dumper->dump($this->cloner->cloneVar($var));
        $html = ob_get_clean();
        $this->dumper = null;
        return $html;
    }

    /**
     * Log a message with specified level
     *
     * @param string $level Log level (info, error, warning, debug)
     * @param mixed $payload Data to log
     * @return bool
     * @throws \RuntimeException
     */
    public function log($level = 'info', $payload = null): bool
    {
        if (is_null($payload)) {
            $payload = $level;
            $level = 'info';
        }

        $payloadHtml = $this->convertToHtml($payload);

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($trace);
        
        $traceHtml = '';
        foreach ($trace as $t) {
            $traceHtml .= sprintf(
                "#%s %s(%s): %s%s%s()\n",
                $t['line'] ?? '',
                $t['file'] ?? '',
                $t['line'] ?? '',
                $t['class'] ?? '',
                $t['type'] ?? '',
                $t['function'] ?? ''
            );
        }

        $data = [
            'level' => strtoupper($level),
            'payload' => base64_encode($payloadHtml),
            'trace' => base64_encode($traceHtml),
            'project' => $this->project,
            'timestamp' => time()
        ];

        try {
            $response = $this->client->request('POST', self::API_URL . '/receive', [
                'json' => $data
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Failed to send log: ' . $response->getBody()->getContents());
            }
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to send log: ' . $e->getMessage());
        }

        return true;
    }
}
