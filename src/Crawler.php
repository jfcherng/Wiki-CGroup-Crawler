<?php

namespace Jfcherng\WikiCGroupCrawler;

use JakeWhiteley\PhpSets\Set;
use QL\QueryList;
use RuntimeException;

class Crawler
{
    public static function crawl(string $url): string
    {
        return self::getHtml($url);
    }

    protected static function getHtml(string $url): string
    {
        $visitedUrls = new Set();

        // find out the actual page (there may be a URL redirection)
        for ($redirectedUrl = $url; $redirectedUrl;) {
            if ($visitedUrls->has($redirectedUrl)) {
                throw new RuntimeException(
                    'URL redirection loop: ' .
                    implode(' -> ', (array) $visitedUrls)
                );
            }

            $visitedUrls->add($redirectedUrl);
            $ql = self::query($redirectedUrl);

            // check for URL soft redirection
            $redirectedUrls = $ql->find('#softredirect > a')->attrs('href')->all();

            if (empty($redirectedUrls)) {
                break;
            }

            $redirectedUrl = head($redirectedUrls);
            // prepend the missing "https://zh.wikipedia.org"
            if (!preg_match('~^https?://~iS', $redirectedUrl)) {
                $baseUrl = preg_match('~^https?://[^/]+~iS', $url, $matches) ? $matches[0] : '';
                $redirectedUrl = "{$baseUrl}{$redirectedUrl}";
            }
        }

        // the editing page is the one which we are actually interested in
        // i.e., URL like https://zh.wikipedia.org/wiki/Template:CGroup/Unit?action=edit
        $ql = self::query(last($visitedUrls->values()), ['action' => 'edit']);

        return $ql->getHtml();
    }

    protected static function query(string $url, ?array $args = null, ?array $headers = null): QueryList
    {
        return QueryList::get($url, $args ?? [], [
            'headers' => $headers ?? [
                'Referer' => preg_match('~https?://[^/]+~S', $url, $matches) ? $matches[0] : '',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.33 Safari/537.36',
            ],
            'timeout' => 20,
            'verify' => false,
        ]);
    }
}
