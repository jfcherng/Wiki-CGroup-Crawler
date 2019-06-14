<?php

declare(strict_types=1);

namespace Jfcherng\WikiCGroupCrawler;

use Exception;
use GuzzleHttp\Client;
use JakeWhiteley\PhpSets\Set;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class Crawler
{
    /**
     * Crawl a Wiki CGroup page.
     *
     * @param string $url The URL
     *
     * @throws RuntimeException (description)
     *
     * @return string the HTML source code of it's editing page
     */
    public static function crawl(string $url): string
    {
        if (!\preg_match('~CGroup/(.*)$~iuS', $url)) {
            return '';
        }

        $redirectedUrl = static::findRedirectedWikiUrl($url);

        // the editing page is the one which we are actually interested in
        // i.e., URL like https://zh.wikipedia.org/wiki/Template:CGroup/Unit?action=edit
        return static::query($redirectedUrl, ['action' => 'edit']);
    }

    /**
     * Crawl Wiki CGroup pages.
     *
     * @param string[] $urls The URL
     *
     * @return string[] the HTML source codes of it's editing page
     */
    public static function crawlMultiple(array $urls): array
    {
        return \array_map(self::class . '::crawl', $urls);
    }

    /**
     * Find the redirected URL of a Wiki link.
     *
     * @param string $url the url
     *
     * @throws RuntimeException if circular URL redirections are detected
     *
     * @return string the redirected URL
     */
    public static function findRedirectedWikiUrl(string $url): string
    {
        static $domCrawler;
        static $visitedUrls;

        $domCrawler = $domCrawler ?? new DomCrawler();
        $visitedUrls = $visitedUrls ?? new Set();

        $domCrawler->clear();
        $visitedUrls->clear();

        // find out the actual page (there may be a URL redirection)
        for ($redirectedUrl = $url; $redirectedUrl;) {
            if ($visitedUrls->has($redirectedUrl)) {
                throw new RuntimeException(
                    'URL redirection loop: ' .
                    \implode(' -> ', (array) $visitedUrls)
                );
            }

            $visitedUrls->add($redirectedUrl);
            $html = static::query($redirectedUrl);

            $domCrawler->addHtmlContent($html, 'UTF-8');

            try {
                // check for URL soft redirection
                $redirectedUrl = $domCrawler
                    ->filter('#softredirect > a')
                    ->first()
                    ->attr('href') ?? '';
            } catch (Exception $e) {
                $redirectedUrl = '';
            }

            $domCrawler->clear();

            if ($redirectedUrl === '') {
                break;
            }

            // prepend the missing "https://zh.wikipedia.org"
            if (!\preg_match('~^https?://~iS', $redirectedUrl)) {
                $baseUrl = \preg_match('~^https?://[^/]+~iS', $url, $matches) ? $matches[0] : '';
                $redirectedUrl = "{$baseUrl}{$redirectedUrl}";
            }
        }

        return last($visitedUrls->values());
    }

    /**
     * Query a URL using GET method with specific HTTP headers.
     *
     * @param string $url     The URL
     * @param array  $args    The GET mothod arguments
     * @param array  $headers The HTTP headers
     *
     * @return string the HTML string
     */
    protected static function query(string $url, array $args = [], array $headers = []): string
    {
        static $client;

        $client = $client ?? new Client();

        $headersDefault = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.72 Safari/537.36',
            'Referer' => \preg_match('~https?://[^/]++~uS', $url, $matches) ? $matches[0] : '',
        ];

        $options = [
            'headers' => $headers + $headersDefault,
            'query' => $args,
            'timeout' => 10,
            'verify' => false,
        ];

        return $client->request('GET', $url, $options)->getBody()->getContents();
    }
}
