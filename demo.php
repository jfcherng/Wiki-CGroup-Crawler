<?php

include __DIR__ . '/vendor/autoload.php';

use Jfcherng\ArrayDumper\DumperFactory;
use Jfcherng\WikiCGroupCrawler\CGroupFacade;

$outputDir = __DIR__ . '/results';

/**
 * URLs to be fetched.
 *
 * @see https://zh.wikipedia.org/wiki/Category:公共轉換組模板
 * @see https://zh.wikipedia.org/wiki/Category:公共转换组模块
 *
 * @var array
 */
$urls = [
    'https://zh.wikipedia.org/wiki/模块:CGroup/Wow',
    'https://zh.wikipedia.org/wiki/Template:CGroup/Movie',
    'https://zh.wikipedia.org/wiki/%E6%A8%A1%E5%9D%97:CGroup/OnePiece',
];

// dump as json, yaml, php, etc...
$dumper = DumperFactory::make('json');

foreach ($urls as $url) {
    $url = \urldecode($url);

    if (!\preg_match('~:CGroup/(.+)$~iuS', $url, $matches)) {
        echo "[SKIP] {$url}\n";
        continue;
    }

    echo "[BEGIN] {$url}\n";

    $moduleName = $matches[1];
    $outputFile = "{$outputDir}/{$moduleName}." . $dumper::EXTENSION;

    // an array of the processed data
    $result = CGroupFacade::fetch($url);

    // dump result to an external file
    $dumper->toFile($result, $outputFile);
}
