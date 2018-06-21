<?php

declare(strict_types=1);

namespace Jfcherng\WikiCGroupCrawler;

use JakeWhiteley\PhpSets\Set;
use QL\QueryList;
use RuntimeException;

class Crawler
{
    /**
     * Crawl a Wiki CGroup page.
     *
     * @param string $url The URL
     *
     * @return string the HTML source code of it's editing page
     */
    public static function crawl(string $url): string
    {
        if (!preg_match('~CGroup/(.*)$~iuS', $url)) {
            return '';
        }

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
            $ql = static::query($redirectedUrl);

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
        $ql = static::query(last($visitedUrls->values()), ['action' => 'edit']);

        return $ql->getHtml();
    }

    /**
     * Crawl Wiki CGroup pages.
     *
     * @param string[] $urls The URL
     *
     * @return string[] the HTML source codes of it's editing page
     */
    public static function crawls(array $urls): array
    {
        return array_map(__CLASS__ . '::crawl', $urls);
    }

    /**
     * Query a URL using GET method with specific HTTP headers.
     *
     * @param string     $url     The URL
     * @param null|array $args    The GET mothod arguments
     * @param null|array $headers The HTTP headers
     *
     * @return QueryList
     */
    protected static function query(string $url, ?array $args = null, ?array $headers = null): QueryList
    {
        $args = $args ?? [];
        $headers = $headers ?? [
            'Referer' => preg_match('~https?://[^/]+~S', $url, $matches) ? $matches[0] : '',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.33 Safari/537.36',
        ];

        return QueryList::get($url, $args, [
            'headers' => $headers,
            'timeout' => 20,
            'verify' => false,
        ]);
    }
}
