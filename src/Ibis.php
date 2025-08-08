<?php

namespace Ibis;

use Ibis\Config\Cover;
use Ibis\Config\Document;
use Ibis\Config\FileList;
use Ibis\Config\Header;
use Ibis\Config\Sample;
use Ibis\Config\Toc;

class Ibis
{
    public Config $config;

    public static function config(?string $configPath = null): Config
    {
        return $configPath === null || $configPath === ''
            ? new Config()
            : self::buildConfigFromJSON($configPath);
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

    protected static function buildConfigFromJSON(string $filePath): Config
    {
        // TODO
    }
}
