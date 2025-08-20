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

    protected function init(string $commandLabel = 'Book Build', string $bookDir = ''): bool
    {
        info('Ibis Next - ' . $commandLabel);
        $this->disk = new Filesystem();

        try {
            $this->config = Ibis::loadConfig(Ibis::basePath(), $bookDir);
        } catch (InvalidConfigFileException $invalidConfigFileException) {
            error($invalidConfigFileException->getMessage());
            info('Did you run `ibis-next init`?');

            return false;
        }

        info(sprintf('✨ Loading config file from: %s ...', $this->config->configFilePath()));

        $contentPath = $this->config->getContentPath();
        if (!file_exists($contentPath) || !is_dir($contentPath)) {
            error(sprintf('Error, check if %s exists', $contentPath));

            return false;
        }

        info(sprintf('✨ Loading content from: %s...', $contentPath));

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
