<?php

namespace App\Services\Import;

class JsonLeadImporter
{
    /**
     * Parse a JSON file and return an array of associative row arrays.
     *
     * @param  string  $filePath  Absolute path to the JSON file.
     * @return array<int, array<string, mixed>>
     *
     * @throws \RuntimeException on parse failure.
     */
    public function parse(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new \RuntimeException("Cannot read file: {$filePath}");
        }

        $decoded = json_decode($content, associative: true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON parse error: '.json_last_error_msg());
        }

        // Accept either a bare array or {"data": [...]}
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            $decoded = $decoded['data'];
        }

        if (! is_array($decoded)) {
            throw new \RuntimeException('JSON root must be an array of lead objects.');
        }

        return $decoded;
    }
}
