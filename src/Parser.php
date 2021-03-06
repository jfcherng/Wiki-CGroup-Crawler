<?php

declare(strict_types=1);

namespace Jfcherng\WikiCGroupCrawler;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use RuntimeException;

class Parser
{
    /**
     * Find rules and parse them from the HTML source codes of a Wiki CGroup page.
     *
     * @param string $html The HTML source codes
     *
     * @return array
     */
    public static function parse(string $html): array
    {
        return (array) (
            static::parseSingleBrace($html) +
            static::parseDoubleBrace($html)
        );
    }

    /**
     * Find rules and parse them from the HTML source codes of Wiki CGroup pages.
     *
     * @param string[] $htmls The HTML source codes
     *
     * @return array[]
     */
    public static function parseMultiple(array $htmls): array
    {
        return \array_map(self::class . '::parse', $htmls);
    }

    /**
     * Find single-brace rules and parse them from the HTML.
     *
     * @param string $html The html
     *
     * @return array
     */
    protected static function parseSingleBrace(string $html): array
    {
        // find the following form in the html
        // { type = 'item', original = 'Nightelf', rule = 'zh-tw:暗夜精灵;zh-cn:夜精靈' }
        if (!\preg_match_all(
            '~^{(?!{) (?: (?!}). )+ }~muxS',
            $html,
            $matches,
            \PREG_SET_ORDER
        )) {
            return [];
        }

        return (new Collection($matches))
            // collect all matches
            ->pluck('0')
            ->map(function (string $item): array {
                $item = static::parseSingleBraceItem($item, true);

                return empty($item) ? [] : $item;
            })
            ->reject(function (array $items): bool {
                return empty($items);
            })
            ->values()
            ->all();
    }

    /**
     * Parse single-brace rules.
     *
     * @param string $item The item
     * @param bool   $tidy Make the result simplier
     *
     * @return array
     */
    protected static function parseSingleBraceItem(string $item, bool $tidy = false): array
    {
        \preg_match_all("~([a-zA-Z]+) \s* = \s* '([^']*)'~uxS", $item, $matches1, \PREG_SET_ORDER);
        \preg_match_all('~([a-zA-Z]+) \s* = \s* "([^"]*)"~uxS', $item, $matches2, \PREG_SET_ORDER);

        /**
         * array(3) {
         *   [0] => array(3) {
         *     [0] => string(13) "type = 'item'"
         *     [1] => string(4) "type"
         *     [2] => string(4) "item"
         *   }
         *   [1] => array(3) {
         *     [0] => string(21) "original = 'Nightelf'"
         *     [1] => string(8) "original"
         *     [2] => string(8) "Nightelf"
         *   }
         *   [2] => array(3) {
         *     [0] => string(44) "rule = 'zh-cn:暗夜精灵;zh-tw:夜精靈;'"
         *     [1] => string(4) "rule"
         *     [2] => string(35) "zh-cn:暗夜精灵;zh-tw:夜精靈;"
         *   }
         * }.
         *
         * @var array
         */
        $matches = $matches1 + $matches2;

        /**
         * array(3) {
         *   ["type"] => string(4) "item"
         *   ["original"] => string(8) "Nightelf"
         *   ["rule"] => string(35) "zh-cn:暗夜精灵;zh-tw:夜精靈;"
         * }.
         *
         * @var array
         */
        $ret = Arr::pluck($matches, '2', '1');

        if (Arr::get($ret, 'type') !== 'item') {
            $ret = [];
        }

        if ($tidy) {
            /**
             * array(3) {
             *   ["original"] => string(8) "Nightelf"
             *   ["zh-cn"] => string(12) "暗夜精灵"
             *   ["zh-tw"] => string(9) "夜精靈"
             * }.
             *
             * @var array
             */
            $ret = static::makeTidy($ret);
        }

        return $ret;
    }

    /**
     * Find double-brace rules and parse them from the HTML.
     *
     * @param string $html The html
     *
     * @return array
     */
    protected static function parseDoubleBrace(string $html): array
    {
        // find the following form in the html
        // {{CItem|zh-cn:千米;zh-tw:公里;zh-hk:公里;zh-sg:公里 |desc=10<sup>3</sup>m|original=[[千米|km]]}}
        if (!\preg_match_all(
            '~^{{(?!{) (?: (?!}}).)+ }}~muxS',
            $html,
            $matches,
            \PREG_SET_ORDER
        )) {
            return [];
        }

        return (new Collection($matches))
            // collect all matches
            ->pluck('0')
            ->map(function (string $item): array {
                $item = static::parseDoubleBraceItem($item, true);

                return empty($item) ? [] : $item;
            })
            ->reject(function (array $items): bool {
                return empty($items);
            })
            ->values()
            ->all();
    }

    /**
     * Parse double-brace rules.
     *
     * @param string $item The item
     * @param bool   $tidy Make the result simplier
     *
     * @throws RuntimeException
     *
     * @return array
     */
    protected static function parseDoubleBraceItem(string $item, bool $tidy = false): array
    {
        // CItem|zh-cn:千米;zh-tw:公里;zh-hk:公里;zh-sg:公里 |desc=10<sup>3</sup>m|original=[[千米|km]]
        $item = \trim($item, " \t\n\r\0\x0B{}");

        \preg_match_all(
            "~
                  original \s* = \s* (?: \[\[ [^\]\r\n]* \]\] | [^|\]}]* )  # original=[[千米|km]]
                | [a-z\-]+ \s* : \s* [^};|\r\n]* (?=\s*(?: [};|] | $))  # zh-cn:千米
                | CItem[^;|}]*  # CItem
            ~iuxS",
            $item,
            $matches,
            \PREG_SET_ORDER
        );

        /**
         * array(6) {
         *   [0] => string(5) "CItem"
         *   [1] => string(12) "zh-cn:千米"
         *   [2] => string(12) "zh-tw:公里"
         *   [3] => string(12) "zh-hk:公里"
         *   [4] => string(13) "zh-sg:公里 "
         *   [5] => string(22) "original=[[千米|km]]"
         * }.
         *
         * @var array
         */
        $ret = Arr::Pluck($matches, '0');

        $ret_ = [];
        $rule = '';
        foreach ($ret as $val) {
            if (\preg_match('~^CItem~iuxS', $val, $matches)) {
                $ret_['type'] = \trim($matches[0]);
            } elseif (\preg_match("~^(?:[a-z\-]+ \s* : \s* [^};|\r\n]*)~iuxS", $val, $matches)) {
                $rule .= \trim($matches[0]) . ';';
            } elseif (\preg_match("~^original \s* =.*~iuxS", $val, $matches)) {
                $ret_['original'] = \trim(\preg_replace("~original \s* =~iuxS", '', $matches[0]));
            } else {
                throw new RuntimeException("Unknown value: {$val}");
            }
        }
        $ret_['rule'] = $rule;

        /**
         * array(3) {
         *   ["type"] => string(5) "CItem"
         *   ["original"] => string(13) "[[千米|km]]"
         *   ["rule"] => string(53) "zh-cn:千米;zh-tw:公里;zh-hk:公里;zh-sg:公里;"
         * }.
         *
         * @var array
         */
        $ret = $ret_;

        if (!\in_array(Arr::get($ret, 'type'), ['CItem', 'CItemHidden'])) {
            $ret = [];
        }

        if ($tidy) {
            /**
             * array(5) {
             *   ["original"] => string(13) "[[千米|km]]"
             *   ["zh-cn"] => string(6) "千米"
             *   ["zh-tw"] => string(6) "公里"
             *   ["zh-hk"] => string(6) "公里"
             *   ["zh-sg"] => string(6) "公里"
             * }.
             *
             * @var array
             */
            $ret = static::makeTidy($ret);
        }

        return $ret;
    }

    /**
     * Make an item array more simple.
     *
     * @param array $item The item
     *
     * @return array
     */
    protected static function makeTidy(array $item): array
    {
        /**
         * item is in the form of the following.
         *
         * array(3) {
         *   ["type"] => string(5) "CItem"
         *   ["original"] => string(13) "[[千米|km]]"
         *   ["rule"] => string(53) "zh-cn:千米;zh-tw:公里;zh-hk:公里;zh-sg:公里;"
         * }
         *
         * @var array
         */
        if (\trim(Arr::get($item, 'rule', '')) === '') {
            return [];
        }

        $ret = [];

        // parse localizations
        foreach (\explode(';', $item['rule']) as $subrule) {
            $subrule = \array_map('trim', \explode(':', $subrule, 2));

            if (!\preg_match('~^[a-zA-Z\-]+$~', $subrule[0])) {
                // something bad happens, skip this subrule
                continue;
            }

            $ret += static::pairToMap($subrule) ?? [];
        }

        // there should be at least two localizations
        if (\count($ret) < 2) {
            return [];
        }

        // add the original text if it exists
        $item['original'] = \trim(Arr::get($item, 'original', ''));
        if ($item['original'] !== '') {
            $ret['original'] = $item['original'];
        }

        /**
         * array(5) {
         *   ["original"] => string(13) "[[千米|km]]"
         *   ["zh-cn"] => string(6) "千米"
         *   ["zh-tw"] => string(6) "公里"
         *   ["zh-hk"] => string(6) "公里"
         *   ["zh-sg"] => string(6) "公里"
         * }.
         *
         * @var array
         */
        return \array_change_key_case($ret, \CASE_LOWER);
    }

    /**
     * Make a map by using a pair as its key and value.
     *
     * @param array $pair The pair
     *
     * @return null|array an array if success, null otherwise
     */
    protected static function pairToMap(array $pair): ?array
    {
        return Arr::has($pair, [0, 1]) ? [$pair[0] => $pair[1]] : null;
    }
}
