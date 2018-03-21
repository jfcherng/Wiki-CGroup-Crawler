<?php

namespace Jfcherng\WikiCGroupCrawler;

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
        $ret = (
            self::parseSingleBrace($html) +
            self::parseDoubleBrace($html)
        );

        return $ret;
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
        if (!preg_match_all(
            '~^{(?!{) (?: (?!}). )+ }~muxS',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            return [];
        }

        $ret = [];

        // collect all matches
        $items = array_pluck($matches, 0);

        foreach ($items as $key => $item) {
            $item = self::parseSingleBraceItem($item, true);

            if (!empty($item)) {
                $ret[] = $item;
            }
        }

        return $ret;
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
        preg_match_all("~([a-zA-Z]+) \s* = \s* '([^']*)'~uxS", $item, $matches1, PREG_SET_ORDER);
        preg_match_all('~([a-zA-Z]+) \s* = \s* "([^"]*)"~uxS', $item, $matches2, PREG_SET_ORDER);

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
        $ret = array_pluck($matches, 2, 1);

        if ($tidy) {
            if (isset($ret['type']) && $ret['type'] !== 'item') {
                $ret = [];
            } else {
                /**
                 * array(3) {
                 *   ["original"] => string(8) "Nightelf"
                 *   ["zh-cn"] => string(12) "暗夜精灵"
                 *   ["zh-tw"] => string(9) "夜精靈"
                 * }.
                 *
                 * @var array
                 */
                $ret = self::makeTidy($ret);
            }
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
        if (!preg_match_all(
            '~^{{(?!{) (?: (?!}}).)+ }}~muxS',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            return [];
        }

        $ret = [];

        // collect all matches
        $items = array_pluck($matches, 0);

        foreach ($items as $key => $item) {
            $item = self::parseDoubleBraceItem($item, true);

            if (!empty($item)) {
                $ret[] = $item;
            }
        }

        return $ret;
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
        $item = trim($item, " \t\n\r\0\x0B{}");

        preg_match_all(
            "~
                  original \s* = \s* \[\[ [^\]\r\n]* \]\]  # original=[[千米|km]]
                | [a-z\-]+ \s* : \s* [^};|\r\n]* (?=\s*[;|])  # zh-cn:千米
                | CItem[^;|}]*  # CItem
            ~iuxS",
            $item,
            $matches,
            PREG_SET_ORDER
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
        $ret = array_pluck($matches, 0);

        $ret_ = [];
        $rule = '';
        foreach ($ret as $val) {
            if (preg_match('~CItem[^;|}]*~iuxS', $val, $matches)) {
                $ret_['type'] = trim($matches[0]);
            } elseif (preg_match('~(?:[a-z\-]+ \s* : \s* [^};|\r\n]*)~iuxS', $val, $matches)) {
                $rule .= trim($matches[0]) . ';';
            } elseif (preg_match('~original \s* = \s* \[\[ [^\]\r\n]* \]\]~iuxS', $val, $matches)) {
                $ret_['original'] = trim(preg_replace('~original \s* = \s* ~iuxS', '', $matches[0]));
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

        if ($tidy) {
            if (isset($ret['type']) && $ret['type'] !== 'CItem') {
                $ret = [];
            } else {
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
                $ret = self::makeTidy($ret);
            }
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
        if (!array_has($item, ['original', 'rule'])) {
            return [];
        }

        $ret = [
            'original' => $item['original'],
        ];

        foreach (explode(';', $item['rule']) as $subrule) {
            $subrule = array_map('trim', explode(':', $subrule, 2));

            if (!preg_match('~^[a-zA-Z\-]+$~', $subrule[0])) {
                // something bad happens, skip this subrule
                continue;
            }

            $ret += self::pairToMap($subrule) ?? [];
        }

        // should at least contains "original" and two localizations
        if (count($ret) < 3) {
            return [];
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
        return array_change_key_case($ret, CASE_LOWER);
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
        return array_has($pair, [0, 1]) ? [$pair[0] => $pair[1]] : null;
    }
}
