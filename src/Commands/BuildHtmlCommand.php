<?php

namespace Ibis\Commands;

use Ibis\Config;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class BuildHtmlCommand extends BaseBuildCommand
{
    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('html')
            ->setDescription('Generate the book in HTML format.');
    }

    /**
     * Execute the command.
     *
     * @throws FileNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->output->writeln('<info>✨ Building HTML file ✨</info>');

        if (!$this->preExecute($input, $output)) {
            return Command::INVALID;
        }

        $this->ensureExportDirectoryExists();
        $this->config->breakLevel(1);

        $result = $this->buildHtmlFile($this->buildHtml());
        $this->output->writeln('');

        if ($result) {
            $this->output->writeln('<info>Book Built Successfully!</info>');
        } else {
            $this->output->writeln('<error>Book Built Failed!</error>');
        }

        return Command::SUCCESS;
    }


    /**
     * @throws FileNotFoundException
     */
    protected function buildHtmlFile(Collection $chapters): bool
    {
        $template = $this->disk->get("{$this->config->getAssetsPath()}/theme-html.html");
        $outputHtml = str_replace("{{\$title}}", $this->config->getTitle(), $template);
        $outputHtml = str_replace("{{\$author}}", $this->config->getAuthor(), $outputHtml);

        $html = "";
        foreach ($chapters as $chapter) {
            $this->output->writeln("<fg=yellow>==></> ❇️ {$chapter["mdfile"]} ...");
            $html .= $chapter["html"];
        }

        $outputHtml = str_replace("{{\$body}}", $html, $outputHtml);
        $htmlFilename = "{$this->config->getExportPath()}/{$this->config->outputFileName()}.html";
        file_put_contents($htmlFilename, $outputHtml);

        $this->output->writeln("<fg=green>==></> HTML file {$htmlFilename} created");
        return true;
    }
}
