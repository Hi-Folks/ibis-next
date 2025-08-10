<?php

namespace Ibis\Commands;

use Ibis\Config;
use Ibis\Exceptions\InvalidConfigFileException;
use Ibis\Ibis;
use Ibis\Markdown\Extensions\Aside;
use Ibis\Markdown\Extensions\AsideExtension;
use Ibis\Markdown\Extensions\AsideRenderer;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use SplFileInfo;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\MarkdownConverter;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;

class BaseBuildCommand extends Command
{
    protected OutputInterface $output;

    protected Filesystem $disk;

    protected string $currentPath;

    protected Config $config;

    protected function preExecute(InputInterface $input, OutputInterface $output): bool
    {
        $this->disk = new Filesystem();
        $this->output = $output;

        try {
            $this->config = Ibis::loadConfig();
        } catch (InvalidConfigFileException $exception) {
            $this->output->writeln("<error>{$exception->getMessage()}</error>");
            $this->output->writeln('<info>Did you run `ibis-next init`?</info>');
            return false;
        }

        $this->output->writeln('<info>Loading config/assets from current directory</info>');
        $this->output->writeln('<info>Loading config file from: ./ibis.php</info>');

        $contentPath = $this->config->getContentPath();
        if (!file_exists($contentPath) || !is_dir($contentPath)) {
            $this->output->writeln("<error>Error, check if {$contentPath} exists.</error>");
            return false;
        }

        $this->output->writeln("<info>Loading content from: {$contentPath} </info>");

        return true;
    }

    protected function buildHtml(bool $extractImages = false): Collection
    {
        $this->output->writeln('<fg=yellow>==></> Parsing Markdown ...');

        $environment = new Environment([]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new FrontMatterExtension());
        $environment->addExtension(new AsideExtension());
        $environment->addExtension(new AttributesExtension());

        $environment->addRenderer(FencedCode::class, new FencedCodeRenderer([
            'html', 'php', 'js', 'bash', 'json',
        ]));
        $environment->addRenderer(IndentedCode::class, new IndentedCodeRenderer([
            'html', 'php', 'js', 'bash', 'json',
        ]));
        $environment->addRenderer(Aside::class, new AsideRenderer());

        if (
            $this->config->getCommonMark() !== []
            && $this->config->getCommonMark()['callback']
            && is_callable($this->config->getCommonMark()['callback'])
        ) {
            call_user_func($this->config->getCommonMark()['callback'], $environment);
        }

        $converter = new MarkdownConverter($environment);

        $fileList = [];
        if ($this->config->getFiles()->files() !== []) {
            foreach ($this->config->getFiles()->files() as $file) {
                $fileList[] = $filefound;
                $filefound = new SplFileInfo("{$this->config->getContentPath()}/{$file}}");
            }
        } else {
            $fileList = $this->disk->allFiles($this->config->getContentPath());
        }

        return collect($fileList)
            ->map(function (SplFileInfo $file, $i) use ($converter, $extractImages) {
                $chapter = collect([]);
                if ($file->getExtension() !== 'md') {
                    $chapter->put("mdfile", $file->getFilename());
                    $chapter->put("frontmatter", false);
                    $chapter->put("html", "");
                    return $chapter;
                }

                $markdown = $this->disk->get(
                    $file->getPathname(),
                );

                if ($extractImages) {
                    $pattern = '/!\[.*?\]\((.*?)\)/';
                    preg_match_all($pattern, $markdown, $matches);
                    $chapter->put("images", $matches[1]);
                }

                $convertedMarkdown = $converter->convert($markdown);
                $chapter->put("mdfile", $file->getFilename());
                $chapter->put("frontmatter", false);
                if ($convertedMarkdown instanceof RenderedContentWithFrontMatter) {
                    $chapter->put("frontmatter", $convertedMarkdown->getFrontMatter());
                }

                $chapter->put("html", $this->prepareHtmlForEbook(
                    $convertedMarkdown->getContent(),
                    $i + 1,
                    $this->config->getBreakLevel() === 0 ? 2 : $this->config->getBreakLevel()
                ));

                return $chapter;
            });
    }

    protected function prepareHtmlForEbook(string $html, int $file, int $breakLevel = 2): string
    {
        $commands = [
            '[break]' => '<div style="page-break-after: always;"></div>',
        ];

        if ($file > 1 && $breakLevel >= 1) {
            $html = str_replace('<h1>', '[break]<h1>', $html);
        }

        if ($breakLevel >= 2) {
            $html = str_replace('<h2>', '[break]<h2>', $html);
        }

        $html = str_replace("<blockquote>\n<p>{notice}", "<blockquote class='notice'><p><strong>Notice:</strong>", $html);
        $html = str_replace("<blockquote>\n<p>{warning}", "<blockquote class='warning'><p><strong>Warning:</strong>", $html);
        $html = str_replace("<blockquote>\n<p>{quote}", "<blockquote class='quote'><p>", $html);
        $html = str_replace("<blockquote>\n<p>[!NOTE]", "<blockquote class='notice'><p><strong>Note:</strong>", $html);
        $html = str_replace("<blockquote>\n<p>[!WARNING]", "<blockquote class='warning'><p><strong>Warning:</strong>", $html);

        return str_replace(array_keys($commands), array_values($commands), $html);
    }

    protected function ensureExportDirectoryExists(): void
    {
        $this->output->writeln('<fg=yellow>==></> Preparing Export Directory ...');
        $exportDir = $this->config->getExportPath();

        if (!$this->disk->isDirectory($exportDir)) {
            $this->disk->makeDirectory($exportDir, 0755, true);
        }
    }

    public function isAbsolutePath($path)
    {
        /*
         * Check to see if the path is a stream and check to see if its an actual
         * path or file as realpath() does not support stream wrappers.
         */
        if ((is_dir($path) || is_file($path))) {
            return true;
        }

        /*
         * This is definitive if true but fails if $path does not exist or contains
         * a symbolic link.
         */
        if (realpath($path) === $path) {
            return true;
        }

        if ((string) $path === '' || '.' === $path[0]) {
            return false;
        }

        // Windows allows absolute paths like this.
        if (preg_match('#^[a-zA-Z]:\\\\#', (string) $path)) {
            return true;
        }

        // A path starting with / or \ is absolute; anything else is relative.
        return ('/' === $path[0] || '\\' === $path[0]);
    }
}
