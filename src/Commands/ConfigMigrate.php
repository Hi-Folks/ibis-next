<?php

namespace Ibis\Commands;

use Ibis\Concerns\HasConfig;
use Ibis\Ibis;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class ConfigMigrate extends Command
{
    use HasConfig;


    protected function configure(): void
    {
        $this->setName('config:migrate')
            ->addOption(
                name: 'book-dir',
                shortcut: 'd',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The base path where the config file is placed',
                default: '',
            )
            ->setDescription('Migrates old array configs to new Config class (v3)');
    }

    /**
     * @throws FileNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //$configFile = "./ibis.php";
        $configJsonFile = "./ibis.json";

        $bookDir = $input->getOption('book-dir');
        $basePath = Ibis::basePath();
        $baseBookPath = Ibis::buildPath([$basePath, $bookDir]);

        $configFile = Ibis::buildPath([
            $baseBookPath,
            Ibis::PHP_CONFIG_FILE,
        ]);

        $configJsonFile = Ibis::buildPath([
            $baseBookPath,
            Ibis::JSON_CONFIG_FILE,
        ]);


        info(sprintf('✨ Loading %s file ...', $configFile));
        if (file_exists($configFile)) {
            $config = require $configFile;
        } else {
            error("Config file : " . $configFile . " doesn't exist.");
            if (! is_dir($baseBookPath)) {
                error("Also the directory : " . $baseBookPath . " doesn't exist.");
            }

            return Command::INVALID;
        }


        if (is_array($config)) {
            info(sprintf('✨ %s file has array format...', $configFile));

            $config["document"]["width"] = $config["document"]["format"][0];
            $config["document"]["height"] = $config["document"]["format"][1];
            unset($config["document"]["format"]);

            $config["toc_levels"]["h1"] = $config["toc_levels"]["H1"];
            $config["toc_levels"]["h2"] = $config["toc_levels"]["H2"];
            $config["toc_levels"]["h3"] = $config["toc_levels"]["H3"];
            unset($config["toc_levels"]["H1"]);
            unset($config["toc_levels"]["H2"]);
            unset($config["toc_levels"]["H3"]);
            if (array_key_exists("sample", $config)) {
                $samplePages = $config["sample"];
                unset($config["sample"]);
                $config["sample"]["pages"] = $samplePages;
            }

            $jsonConfig = json_encode($config, flags: JSON_PRETTY_PRINT);


            if (file_exists($configJsonFile)) {
                $timestamp = date("Ymd_His");
                $backupFile = sprintf('./ibis.backup.%s.json', $timestamp);

                if (!copy($configJsonFile, $backupFile)) {
                    error('Failed to create backup of ${configJsonFile}.');
                    return Command::FAILURE;
                }

                info('Backup created at ' . $backupFile);

            }

            if (file_put_contents($configJsonFile, $jsonConfig) === false) {
                error('Failed to write new config into ${configJsonFile}.');
                return Command::FAILURE;
            }

            info('New config written to ' . $configJsonFile);
            info('✅ Done!');

        } else {
            info(sprintf('The config file %s does not seem to be in the old format. No conversion is required.', $configFile));
        }





        return Command::SUCCESS;
    }


}
