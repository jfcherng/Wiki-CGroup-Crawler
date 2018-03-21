<?php

namespace Jfcherng\WikiCGroupCrawler;

class Utility
{
    /**
     * A customized version of "var_export()".
     *
     * @param mixed $expression  The expression
     * @param bool  $return      Return the result, otherwise echo
     * @param int   $indentLevel Numbers of spaces used as indentation
     * @param bool  $minify      Minify the array output?
     *
     * @return mixed
     */
    public static function myVarExport($expression, bool $return = false, int $indentLevel = 4, bool $minify = false)
    {
        $object = json_decode(str_replace(['(', ')'], ['&#40', '&#41'], json_encode($expression)), true);
        $indent = str_repeat(' ', $indentLevel);

        $export = strtr(var_export($object, true), [
            'array (' => '[',
            ')' => ']',
            '&#40' => '(',
            '&#41' => ')',
        ]);
        $export = preg_replace("/ => \n[^\S\n]*\[/m", ' => [', $export);
        $export = preg_replace("/ => \[\n[^\S\n]*\]/m", ' => []', $export);
        $export = preg_replace('/([ ]{2})(?![^ ])/m', $indent, $export);
        $export = preg_replace('/^([ ]{2})/m', $indent, $export);

        if ($minify) {
            if (is_array($expression)) {
                $export = preg_replace("~(['\"]) => (['\"])~S", '$1=>$2', $export);
            }
        }

        if ($return) {
            return $export;
        }

        echo $export;
    }
}
