<?php

declare(strict_types=1);

namespace Jfcherng\WikiCGroupCrawler;

class CGroupFacade
{
    /**
     * Get the parsed results of a URL.
     *
     * @param string $url ghe url
     *
     * @return array
     */
    public static function fetch(string $url): array
    {
        return Parser::parse(Crawler::crawl($url));
    }

    /**
     * Get the parsed results of URLs.
     *
     * @param array $urls the urls
     *
     * @return array[]
     */
    public static function fetchMultiple(array $urls): array
    {
        return \array_map(__CLASS__ . '::fetch', $urls);
    }
}
