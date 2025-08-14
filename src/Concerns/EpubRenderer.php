<?php

namespace Ibis\Concerns;

use Ibis\Enums\OutputFormat;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use PHPePub\Core\EPub;

use function Laravel\Prompts\info;

trait EpubRenderer
{
    protected function buildEpubFile(OutputFormat $outputFormat): string
    {
        $this->config->breakLevel(1);

        $chapters = $this->buildHtml(true);
        $content_start =
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:epub=\"http://www.idpf.org/2007/ops\">\n"
            . "<head>"
            . "<meta http-equiv=\"Default-Style\" content=\"text/html; charset=utf-8\" />\n"
            . "<link rel=\"stylesheet\" type=\"text/css\" href=\"codeblock.css\" />\n"
            . "<link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\" />\n"
            . "<title>{$this->config->getTitle()}</title>\n"
            . "</head>\n"
            . "<body>\n";
        $content_end = "</body></html>";

        $book = new EPub(EPub::BOOK_VERSION_EPUB3, "en", EPub::DIRECTION_LEFT_TO_RIGHT);
        $book->setIdentifier(md5("{$this->config->getTitle()} - {$this->config->getAuthor()}"), EPub::IDENTIFIER_UUID);
        $book->setLanguage("en");
        $book->setDescription("{$this->config->getTitle()} - {$this->config->getAuthor()}");
        $book->setTitle($this->config->getTitle());
        $book->setAuthor($this->config->getAuthor(), $this->config->getAuthor());
        $book->setIdentifier("{$this->config->getTitle()}&amp;stamp=" . time(), EPub::IDENTIFIER_URI);

        $book->addCSSFile("style.css", "css1", $this->getStyle("style"));
        $book->addCSSFile(
            "codeblock.css",
            "css2",
            $this->getStyle("highlight.codeblock.min"),
        );

        $cover = $content_start . "<h1>{$this->config->getTitle()}</h1>\n";
        if ($this->config->getAuthor()) {
            $cover .= "<h2>By: {$this->config->getAuthor()}</h2>\n";
        }

        $cover .= $content_end;

        $coverConfig = $this->config->getCover();
        $pathCoverImage = "{$this->config->getAssetsPath()}/{$coverConfig->getSrc()}";
        if ($this->disk->isFile($pathCoverImage)) {
            info("-> ✨ Adding Book Cover {$pathCoverImage} ...");
            $book->setCoverImage('cover.jpg', file_get_contents($pathCoverImage), mime_content_type($pathCoverImage));
        }

        $book->addChapter("Cover", "Cover.html", $cover);
        $book->addChapter("Table of Contents", "TOC.xhtml", null, false, EPub::EXTERNAL_REF_IGNORE);

        /*
        $book->addFileToMETAINF("com.apple.ibooks.display-options.xml", "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<display_options>\n    <platform name=\"*\">\n        <option name=\"fixed-layout\">true</option>\n        <option name=\"interactive\">true</option>\n        <option name=\"specified-fonts\">true</option>\n    </platform>\n</display_options>");
        */
        $book->addCustomNamespace("dc", "http://purl.org/dc/elements/1.1/"); // StaticData::$namespaces["dc"]);


        foreach ($chapters as $key => $chapter) {
            info("-> ❇️ {$chapter["mdfile"]} ...");

            // fixing html
            $chapter["html"] = str_replace("</span> <span", "</span>&nbsp;<span", $chapter["html"]);
            $book->addChapter(
                chapterName: Arr::get($chapter, "frontmatter.title", "Chapter " . ($key + 1)),
                fileName: "Chapter" . $key . ".html",
                chapterData: $content_start . $chapter["html"] . $content_end,
                externalReferences: EPub::EXTERNAL_REF_ADD,
            );
            foreach (Arr::get($chapter, "images", []) as $idxImage => $markdownPathImage) {
                if (filter_var($markdownPathImage, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $pathImage = $markdownPathImage;
                if (! $this->isAbsolutePath($markdownPathImage)) {
                    $pathImage = "{$this->config->getContentPath()}/{$markdownPathImage}";
                }

                if (!file_exists($pathImage)) {
                    continue;
                }

                $book->addLargeFile(
                    $markdownPathImage,
                    "image-" . $key . "-" . $idxImage,
                    $pathImage,
                    mime_content_type($pathImage),
                );
            }
        }

        $book->buildTOC(title: "Index", addReferences: false);
        $book->finalize();

        $filename = "{$this->config->getExportPath()}/{$this->config->outputFileName()}{$outputFormat->extension()}";
        @$book->saveBook($filename);

        return $filename;
    }

    /**
     * @throws FileNotFoundException
     */
    private function getStyle(string $themeName): string
    {
        return $this->disk->get("{$this->config->getAssetsPath()}/{$themeName}.css");
    }
}
