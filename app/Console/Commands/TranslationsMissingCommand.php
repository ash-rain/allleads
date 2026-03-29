<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class TranslationsMissingCommand extends Command
{
    protected $signature   = 'translations:missing
                              {--locale=en : The locale to check}
                              {--fail : Exit with non-zero status if keys are missing}';

    protected $description = 'Find translation keys used in views/PHP that are missing from the lang files';

    /**
     * Regex patterns that capture translation keys.
     * Matches: __('key'), trans('key'), @lang('key'), __("key")
     */
    private const KEY_PATTERN = '/(?:__|trans|@lang)\s*\(\s*[\'"]([a-zA-Z0-9_.]+)[\'"]/';

    public function handle(): int
    {
        $locale = $this->option('locale');

        $this->info("Scanning for translation keys used in source files…");

        $usedKeys      = $this->collectUsedKeys();
        $definedKeys   = $this->collectDefinedKeys($locale);
        $missingKeys   = array_diff($usedKeys, $definedKeys);

        if (empty($missingKeys)) {
            $this->info('✅  No missing translation keys found.');
            return self::SUCCESS;
        }

        $this->warn(sprintf('❌  %d missing key(s) for locale "%s":', count($missingKeys), $locale));

        $rows = array_map(fn($key) => [$key], $missingKeys);
        $this->table(['Missing Key'], $rows);

        return $this->option('fail') ? self::FAILURE : self::SUCCESS;
    }

    /** @return string[] */
    private function collectUsedKeys(): array
    {
        $keys  = [];
        $paths = [
            resource_path('views'),
            app_path(),
        ];

        foreach ($paths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            /** @var SplFileInfo[] $files */
            $files = File::allFiles($path);

            foreach ($files as $file) {
                if (! in_array($file->getExtension(), ['php', 'blade.php'], true)) {
                    continue;
                }

                preg_match_all(self::KEY_PATTERN, $file->getContents(), $matches);
                foreach ($matches[1] as $key) {
                    $keys[] = $key;
                }
            }
        }

        return array_unique($keys);
    }

    /** @return string[] */
    private function collectDefinedKeys(string $locale): array
    {
        $langPath = lang_path($locale);
        $keys     = [];

        if (! File::isDirectory($langPath)) {
            return $keys;
        }

        foreach (File::allFiles($langPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group  = $file->getFilenameWithoutExtension();
            $values = require $file->getRealPath();

            foreach ($this->flattenKeys($values, $group) as $key) {
                $keys[] = $key;
            }
        }

        return array_unique($keys);
    }

    /**
     * Flatten a nested translation array to "group.key" dotted strings.
     *
     * @param  array<string, mixed>  $array
     * @return string[]
     */
    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];

        foreach ($array as $key => $value) {
            $full = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                foreach ($this->flattenKeys($value, $full) as $nested) {
                    $keys[] = $nested;
                }
            } else {
                $keys[] = $full;
            }
        }

        return $keys;
    }
}
