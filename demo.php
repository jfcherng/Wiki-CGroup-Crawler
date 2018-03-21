<?php

include __DIR__ . '/vendor/autoload.php';

use Jfcherng\WikiCGroupCrawler\Crawler;
use Jfcherng\WikiCGroupCrawler\Parser;
use Jfcherng\WikiCGroupCrawler\Utility;

$outputDir = __DIR__ . '/results';

// maybe check the following URLs to find modules
// https://zh.wikipedia.org/wiki/Category:公共轉換組模板
// https://zh.wikipedia.org/wiki/Category:公共转换组模块
$urls = [
    'https://zh.wikipedia.org/wiki/模块:CGroup/Wow',
    'https://zh.wikipedia.org/wiki/Template:CGroup/Movie',
    'https://zh.wikipedia.org/wiki/Template:CGroup/Unit',
];

!is_dir($outputDir) && mkdir($outputDir, 0777, true);

foreach ($urls as $url) {
    if (!preg_match('~CGroup/(.*)$~iuS', $url, $matches)) {
        continue;
    }

    $moduleName = urldecode($matches[1]);
    $outputFile = "{$outputDir}/{$moduleName}.php";

    $crawled = Crawler::crawl($url);
    $parsed = Parser::parse($crawled);
    $resultsPhp = Utility::myVarExport($parsed, true, 4, true);

    $outputContent = <<<EOF
<?php

// generated by Jfcherng.WikiCGroupCrawler
return ${resultsPhp};

EOF;

    file_put_contents($outputFile, $outputContent);
}
