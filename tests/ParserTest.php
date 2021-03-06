<?php

namespace Jfcherng\WikiCGroupCrawler\Test;

use Jfcherng\WikiCGroupCrawler\Parser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Jfcherng\WikiCGroupCrawler\Parser
 *
 * @internal
 */
final class ParserTest extends TestCase
{
    /**
     * Provides testcases.
     *
     * @return array the testcases
     */
    public function parserParseDataProvider(): array
    {
        return [
            // the first testcase
            [
                // input
                \implode(\PHP_EOL, [
                    'blah blah blah',
                    "{ type = 'item', rule = 'zh-cn:美国宇航局; zh-tw:美國國家航空暨太空總署; zh-hk:美國太空總署;', original = 'NASA' },",
                    "{ type = 'item', rule = 'zh-cn:阿贝尔星系团表; zh-tw:艾伯耳星系團表;', original = '[[:en:Abell catalogue|Abell catalogue]]' },",
                    'blah blah blah',
                ]),
                // expected output
                [
                    [
                        'original' => 'NASA',
                        'zh-cn' => '美国宇航局',
                        'zh-tw' => '美國國家航空暨太空總署',
                        'zh-hk' => '美國太空總署',
                    ],
                    [
                        'original' => '[[:en:Abell catalogue|Abell catalogue]]',
                        'zh-cn' => '阿贝尔星系团表',
                        'zh-tw' => '艾伯耳星系團表',
                    ],
                ],
            ],
            // more testcases...
            [
                \implode(\PHP_EOL, [
                    'blah blah blah',
                    '{{CItem|zh-hans:残疾人奥林匹克运动会;zh-hk:傷殘奧林匹克運動會;zh-tw:帕拉林匹克運動會;|original=}}',
                    'blah blah blah',
                    '{{CItemHidden|original=Shawshank Redemption, The|zh-cn:肖申克的救赎;zh-hk:月黑高飛;zh-tw:刺激1995}}',
                    'blah blah blah',
                ]),
                [
                    [
                        'zh-hans' => '残疾人奥林匹克运动会',
                        'zh-tw' => '帕拉林匹克運動會',
                        'zh-hk' => '傷殘奧林匹克運動會',
                    ],
                    [
                        'original' => 'Shawshank Redemption, The',
                        'zh-cn' => '肖申克的救赎',
                        'zh-hk' => '月黑高飛',
                        'zh-tw' => '刺激1995',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test Parser::parse().
     *
     * @dataProvider parserParseDataProvider
     *
     * @param string $input    the input
     * @param array  $expected the expected output
     */
    public function testParse(string $input, array $expected): void
    {
        $output = Parser::parse($input);

        static::assertSame(
            \arraySortedRecursive($expected, 'asort'),
            \arraySortedRecursive($output, 'asort')
        );
    }
}
