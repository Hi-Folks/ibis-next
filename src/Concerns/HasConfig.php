<?php

namespace Ibis\Concerns;

use Ibis\Config;
use Ibis\Exceptions\InvalidConfigFileException;
use Ibis\Ibis;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

trait HasConfig
{
    protected Filesystem $disk;

    protected Config $config;

    protected function init(string $commandLabel = 'Book Build'): bool
    {
        info("Ibis Next - {$commandLabel}");

        $this->disk = new Filesystem();

        try {
            $this->config = Ibis::loadConfig();
        } catch (InvalidConfigFileException $exception) {
            error($exception->getMessage());
            info('Did you run `ibis-next init`?');

            return false;
        }

        info('✨ Loading config/assets from current directory...');
        info('✨ Loading config file from: ./ibis.php ...');

        $contentPath = $this->config->getContentPath();
        if (!file_exists($contentPath) || !is_dir($contentPath)) {
            error("Error, check if {$contentPath} exists");

            return false;
        }

        info("✨ Loading content from: {$contentPath}...");

        return true;
    }

    protected function showResultTable(array $createdFiles): void
    {
        $formatted = [];
        foreach ($createdFiles as $outputFormat => $file) {
            $formatted[] = [$outputFormat, $file];
        }

        table(
            headers: ['OUTPUT FORMAT', 'FILE'],
            rows: $formatted,
        );
    }
}
