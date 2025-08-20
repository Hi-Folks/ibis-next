<?php

namespace Ibis\Concerns;

use Ibis\Enums\OutputFormat;
use Ibis\Ibis;
use Ibis\Markdown\Extensions\Aside;
use Ibis\Markdown\Extensions\AsideExtension;
use Ibis\Markdown\Extensions\AsideRenderer;
use Illuminate\Support\Collection;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;
use SplFileInfo;

use function Laravel\Prompts\info;

trait HtmlRenderer
{
    protected function buildHtmlFile(OutputFormat $outputFormat): string
    {
        $this->config->breakLevel(1);

        $template = $this->disk->get(Ibis::buildPath([$this->config->getAssetsPath(), 'theme-html.html']));
        $outputHtml = str_replace("{{\$title}}", $this->config->getTitle(), $template);
        $outputHtml = str_replace("{{\$author}}", $this->config->getAuthor(), $outputHtml);

        $chapters = $this->buildHtml();
        $html = '';
        foreach ($chapters as $chapter) {
            info(sprintf('-> ❇️ %s ...', $chapter["mdfile"]));
            $html .= $chapter["html"];
        }

        $outputHtml = str_replace("{{\$body}}", $html, $outputHtml);
        $filename = Ibis::buildPath([
            $this->config->getExportPath(),
            $this->config->outputFileName() . $outputFormat->extension(),
        ]);
        file_put_contents($filename, $outputHtml);

        return $filename;
    }

    protected function buildHtml(bool $extractImages = false): Collection
    {
        info('Parsing Markdown ...');

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
                $fileList[] = new SplFileInfo(Ibis::buildPath([$this->config->getContentPath(), $file]));

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
                    $this->config->getBreakLevel() === 0 ? 2 : $this->config->getBreakLevel(),
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
}
