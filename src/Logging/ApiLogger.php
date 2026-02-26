<?php

declare(strict_types=1);

namespace Freshdesk\Logging;

use Freshdesk\Exceptions\ApiException;

class ApiLogger
{
    private const FIELDS_TO_TRIM = ['description', 'description_text', 'body', 'body_text'];
    private const MAX_FIELD_LENGTH = 100;

    /** Buffered request line for errors-only mode */
    private ?string $bufferedRequestLine = null;

    public function __construct(
        private readonly string $logDirectory,
        private readonly string $level = 'all',
    ) {
    }

    public function logRequest(string $method, string $endpoint, ?array $data): void
    {
        $dataString = $data !== null ? ' - ' . $this->encodeData($data) : '';
        $line = $this->formatLine('Request - ' . $method . ' - ' . $endpoint . $dataString);

        if ($this->level === 'all') {
            $this->writeToAllFile($line);
        } else {
            // In errors-only mode, buffer the request line
            $this->bufferedRequestLine = $line;
        }
    }

    public function logResponse(string $method, string $endpoint, mixed $result): void
    {
        $resultString = $result !== null ? ' - ' . $this->encodeData($result) : '';
        $line = $this->formatLine('Response - ' . $method . ' - ' . $endpoint . $resultString);

        if ($this->level === 'all') {
            $this->writeToAllFile($line);
        }

        // Discard buffered request on success
        $this->bufferedRequestLine = null;
    }

    public function logErrorResponse(string $method, string $endpoint, \Throwable $e): void
    {
        $errorDetail = $this->formatErrorDetail($e);
        $line = $this->formatLine('Response - ' . $method . ' - ' . $endpoint . ' - ' . $errorDetail);

        if ($this->level === 'all') {
            $this->writeToAllFile($line);
        }

        // Write buffered request + error response to error file
        if ($this->bufferedRequestLine !== null) {
            $this->writeToErrorFile($this->bufferedRequestLine);
            $this->bufferedRequestLine = null;
        }

        $this->writeToErrorFile($line);
    }

    private function formatErrorDetail(\Throwable $e): string
    {
        if ($e instanceof ApiException) {
            $requestException = $e->getRequestException();
            $code = $requestException->getCode();
            $body = $e->getRequestBody();

            return 'ERROR ' . $code . ($body !== null ? ' - ' . $body : '');
        }

        return 'ERROR - ' . $e->getMessage();
    }

    private function formatLine(string $message): string
    {
        $datetime = new \DateTimeImmutable();

        return '[' . $datetime->format('Y-m-d H:i:s.u') . '] ' . $message . "\n";
    }

    private function encodeData(mixed $data): string
    {
        if (is_array($data) || is_object($data)) {
            $data = $this->trimFields($data);
        }

        try {
            return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException) {
            return '(non-encodable data)';
        }
    }

    /**
     * @param array<mixed>|object $data
     * @return array<mixed>
     */
    private function trimFields(array|object $data): array
    {
        $array = is_object($data) ? (array) $data : $data;

        unset($array['attachments']);

        foreach (self::FIELDS_TO_TRIM as $field) {
            if (isset($array[$field]) && is_string($array[$field]) && mb_strlen($array[$field]) > self::MAX_FIELD_LENGTH) {
                $array[$field] = mb_substr($array[$field], 0, self::MAX_FIELD_LENGTH) . '...';
            }
        }

        return $array;
    }

    private function writeToAllFile(string $line): void
    {
        $date = new \DateTimeImmutable();
        $path = $this->logDirectory . '/freshdesk-all-' . $date->format('Y-m') . '.log';
        $this->writeLine($path, $line);
    }

    private function writeToErrorFile(string $line): void
    {
        $date = new \DateTimeImmutable();
        $path = $this->logDirectory . '/freshdesk-error-' . $date->format('Y-m') . '.log';
        $this->writeLine($path, $line);
    }

    private function writeLine(string $path, string $line): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
