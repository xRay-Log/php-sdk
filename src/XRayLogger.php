<?php

namespace XRayLog;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class XRayLogger
{
    private const DOCKER_HOST = 'host.docker.internal';
    private const LOCAL_HOST = 'localhost';
    private const PORT = '44827';

    private $project;
    private $client;
    private $cloner;
    private $dumper;
    private $apiUrl;

    public function __construct(string $project, ?ClientInterface $client = null)
    {
        $this->project = $project;
        $this->client = $client ?? new Client();
        $this->cloner = new VarCloner();
        $this->apiUrl = $this->determineApiUrl();
    }

    private function determineApiUrl(): string
    {
        $host = self::LOCAL_HOST;
        
        // Check if host.docker.internal is accessible
        $dockerSocket = @fsockopen(self::DOCKER_HOST, self::PORT, $errno, $errstr, 1);
        if ($dockerSocket) {
            $host = self::DOCKER_HOST;
            fclose($dockerSocket);
        }
        
        return sprintf('http://%s:%s', $host, self::PORT);
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
        $this->dumper->setDumpHeader("");
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
        
        if((is_object($payload) || is_resource($payload))) {
            $payload = $this->convertToHtml($payload);
        } else {
            $payload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

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
            'payload' => $payload,
            'trace' => $traceHtml,
            'project' => $this->project,
            'timestamp' => time()
        ];
    
        try {
            $response = $this->client->request('POST', $this->apiUrl . '/receive', [
                'body' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'headers' => ['Content-Type' => 'application/json']
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
