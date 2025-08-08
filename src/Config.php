<?php

namespace Ibis;

use Ibis\Config\Cover;
use Ibis\Config\Document;
use Ibis\Config\FileList;
use Ibis\Config\Font;
use Ibis\Config\Header;
use Ibis\Config\Sample;
use Ibis\Config\Toc;

class Config
{
    private string $title;

    private string $author;

    private string $contentPath;

    private string $exportPath;

    /**
     * @var array<Font>
     */
    private array $fonts = [];

    private array $commonMark;

    private Document $document;

    private Toc $toc;

    private Cover $cover;

    private Header $header;

    private Sample $sample;

    private FileList $files;

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function author(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function contentPath(string $contentPath): self
    {
        $this->contentPath = $contentPath;

        return $this;
    }

    public function exportPath(string $exportPath): self
    {
        $this->exportPath = $exportPath;

        return $this;
    }

    public function addFont(Font $font): self
    {
        $this->fonts[] = $font;

        return $this;
    }

    public function commonMark(array $commonMark): self
    {
        $this->commonMark = $commonMark;

        return $this;
    }

    public function document(Document $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function toc(Toc $toc): self
    {
        $this->toc = $toc;

        return $this;
    }

    public function cover(Cover $cover): self
    {
        $this->cover = $cover;

        return $this;
    }

    public function header(Header $header): self
    {
        $this->header = $header;

        return $this;
    }

    public function sample(Sample $sample): self
    {
        $this->sample = $sample;

        return $this;
    }

    public function files(FileList $files): self
    {
        $this->files = $files;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getContentPath(): string
    {
        return $this->contentPath;
    }

    public function getExportPath(): string
    {
        return $this->exportPath;
    }

    public function getFonts(): array
    {
        return $this->fonts;
    }

    public function getCommonMark(): array
    {
        return $this->commonMark;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getToc(): Toc
    {
        return $this->toc;
    }

    public function getCover(): Cover
    {
        return $this->cover;
    }

    public function getHeader(): Header
    {
        return $this->header;
    }

    public function getSample(): Sample
    {
        return $this->sample;
    }

    public function getFiles(): FileList
    {
        return $this->files;
    }
}
