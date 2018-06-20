<?php

include __DIR__ . '/vendor/autoload.php';

use Jfcherng\ArrayDumper\DumperFactory;
use Jfcherng\WikiCGroupCrawler\Crawler;
use Jfcherng\WikiCGroupCrawler\Parser;

$outputDir = __DIR__ . '/results';

// maybe check the following URLs to find modules
// https://zh.wikipedia.org/wiki/Category:公共轉換組模板
// https://zh.wikipedia.org/wiki/Category:公共转换组模块
$urls = [
    'https://zh.wikipedia.org/wiki/模块:CGroup/Wow',
    'https://zh.wikipedia.org/wiki/Template:CGroup/Movie',
    'https://zh.wikipedia.org/wiki/%E6%A8%A1%E5%9D%97:CGroup/OnePiece',
];

$dumper = DumperFactory::make('yaml', [
    'indent' => 2,
]);

foreach ($urls as $url) {
    if (!preg_match('~CGroup/(.*)$~iuS', $url, $matches)) {
        continue;
    }

    $moduleName = urldecode($matches[1]);
    $outputFile = "{$outputDir}/{$moduleName}." . $dumper::EXTENSION;

    $crawled = Crawler::crawl($url);
    $parsed = Parser::parse($crawled);

    $dumper->toFile($parsed, $outputFile);
}
