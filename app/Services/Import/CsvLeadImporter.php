<?php

namespace App\Services\Import;

use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class CsvLeadImporter
{
    /**
     * Parse a CSV file and return an array of associative row arrays.
     *
     * @param  string  $filePath  Absolute path to the CSV file.
     * @return array<int, array<string, string>>
     *
     * @throws \RuntimeException on parse failure.
     */
    public function parse(string $filePath): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);

            // Handle UTF-8 BOM
            $csv->skipEmptyRecords();

            $records = [];
            foreach ($csv->getRecords() as $record) {
                $records[] = array_map('trim', $record);
            }

            return $records;
        } catch (CsvException $e) {
            throw new \RuntimeException('CSV parse error: ' . $e->getMessage(), 0, $e);
        }
    }
}
