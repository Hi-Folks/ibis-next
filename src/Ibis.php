<?php

namespace Ibis;

use Ibis\Concerns\PathManager;
use Ibis\Config\Cover;
use Ibis\Config\Document;
use Ibis\Config\FileList;
use Ibis\Config\Font;
use Ibis\Config\Header;
use Ibis\Config\Sample;
use Ibis\Config\Toc;
use Ibis\Exceptions\InvalidConfigFileException;
use Illuminate\Support\Str;

class Ibis
{
    use PathManager;

    public const JSON_CONFIG_FILE = 'ibis.json';

    public const PHP_CONFIG_FILE = 'ibis.php';

    /**
     * @throws InvalidConfigFileException
     */
    public static function config(?string $configPath = null): Config
    {
        return $configPath === null || $configPath === ''
            ? new Config()
            : self::buildConfigFromJSON($configPath);
    }

    /**
     * @throws InvalidConfigFileException
     */
    public static function loadConfig(string $basePath, string $bookPath): Config
    {
        $configPath = Str::deduplicate(implode('/', [$basePath, $bookPath, Ibis::JSON_CONFIG_FILE]), '/');
        if (file_exists($configPath)) {
            $config = self::config($configPath);

            return $config->basePath($basePath)
                ->bookPath($bookPath)
                ->jsonConfig(true);
        }

        $configPath = str_replace(Ibis::JSON_CONFIG_FILE, Ibis::PHP_CONFIG_FILE, $configPath);
        if (! file_exists($configPath)) {
            throw InvalidConfigFileException::fileDoesNotExist($configPath);
        }

        $config = self::requireConfigFile($configPath);

        return $config->basePath($basePath)
            ->bookPath($bookPath);
    }

    public static function requireConfigFile(string $configPath): Config
    {
        return require $configPath;
    }

    public static function document(): Document
    {
        return new Document();
    }

    public static function toc(): Toc
    {
        return new Toc();
    }

    public static function cover(): Cover
    {
        return new Cover();
    }

    public static function header(): Header
    {
        return new Header();
    }

    public static function sample(): Sample
    {
        return new Sample();
    }

    public static function files(): FileList
    {
        return new FileList();
    }

    /**
     * @throws InvalidConfigFileException
     */
    protected static function buildConfigFromJSON(string $filePath): Config
    {
        if (!file_exists($filePath)) {
            throw InvalidConfigFileException::fileDoesNotExist($filePath);
        }

        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw InvalidConfigFileException::invalidConfigFile($filePath);
        }

        $config = new Config();

        if (isset($data['title'])) {
            $config->title($data['title']);
        }

        if (isset($data['author'])) {
            $config->author($data['author']);
        }

        if (isset($data['content_path'])) {
            $config->contentPath($data['content_path']);
        }

        if (isset($data['fonts']) && is_array($data['fonts'])) {
            foreach ($data['fonts'] as $fontName => $fontSrc) {
                $config->addFont(new Font($fontName, $fontSrc));
            }
        }

        if (isset($data['commonmark']) && is_array($data['commonmark'])) {
            $config->commonMark($data['commonmark']);
        }

        if (isset($data['document']) && is_array($data['document'])) {
            $config->document(self::buildDocumentConfigFromArray($data['document']));
        }

        if (isset($data['toc_levels']) && is_array($data['toc_levels'])) {
            $config->toc(self::buildTocConfigFromArray($data['toc_levels']));
        }

        if (isset($data['cover']) && is_array($data['cover'])) {
            $config->cover(self::buildCoverConfigFromArray($data['cover']));
        }

        if (isset($data['header']) && is_array($data['header'])) {
            $config->header(self::buildHeaderConfigFromArray($data['header']));
        }

        if (isset($data['sample']) && is_array($data['sample'])) {
            $config->sample(self::buildSampleConfigFromArray($data['sample']));
        }

        $config->files(new FileList());
        if (isset($data['files']) && is_array($data['files'])) {
            $config->files(self::buildFilesConfigFromArray($data['files']));
        }

        return $config;
    }

    protected static function buildDocumentConfigFromArray(array $config): Document
    {
        $document = new Document();

        if (isset($config['height'])) {
            $document->height($config['height']);
        }

        if (isset($config['width'])) {
            $document->width($config['width']);
        }

        if (isset($config['margin_left'])) {
            $document->marginLeft($config['margin_left']);
        }

        if (isset($config['margin_right'])) {
            $document->marginRight($config['margin_right']);
        }

        if (isset($config['margin_top'])) {
            $document->marginTop($config['margin_top']);
        }

        if (isset($config['margin_bottom'])) {
            $document->marginBottom($config['margin_bottom']);
        }

        return $document;
    }

    protected static function buildTocConfigFromArray(array $config): Toc
    {
        $toc = new Toc();

        if (isset($config['h1'])) {
            $toc->h1($config['h1']);
        }
        if (isset($config['h2'])) {
            $toc->h2($config['h2']);
        }
        if (isset($config['h3'])) {
            $toc->h3($config['h3']);
        }

        return $toc;
    }

    protected static function buildCoverConfigFromArray(array $config): Cover
    {
        $cover = new Cover();

        if (isset($config['image'])) {
            $cover->src($config['image']);
        }
        if (isset($config['position'])) {
            $cover->position($config['position']);
        }

        if (isset($config['height'])) {
            $cover->height($config['height']);
        }
        if (isset($config['width'])) {
            $cover->width($config['width']);
        }
        if (isset($config['left'])) {
            $cover->left($config['left']);
        }
        if (isset($config['right'])) {
            $cover->right($config['right']);
        }
        if (isset($config['top'])) {
            $cover->top($config['top']);
        }
        if (isset($config['bottom'])) {
            $cover->bottom($config['bottom']);
        }

        return $cover;
    }

    protected static function buildHeaderConfigFromArray(array $config): Header
    {
        $header = new Header();

        if (isset($config['style'])) {
            $header->style($config['style']);
        }
        if (isset($config['text'])) {
            $header->text($config['text']);
        }

        return $header;
    }

    protected static function buildSampleConfigFromArray(array $config): Sample
    {
        $sample = new Sample();

        if (isset($config['text'])) {
            $sample->text($config['text']);
        }
        if (isset($config['pages']) && is_array($config['pages'])) {
            foreach ($config['pages'] as $pageList) {
                $sample->addPages($pageList[0], $pageList[1]);
            }
        }

        return $sample;
    }

    protected static function buildFilesConfigFromArray(array $files): FileList
    {
        $fileList = new FileList();

        foreach ($files as $file) {
            $fileList->addFile($file);
        }

        return $fileList;
    }
}
