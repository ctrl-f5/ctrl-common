<?php

namespace Ctrl\Common\Tools;

class ArrayHelper
{
    /**
     * If $caseSensitive is true, string values are transformed to lower case
     *
     * @param array $array
     * @param bool $caseSensitive
     * @return array
     */
    public static function countValues(array $array, $caseSensitive = true)
    {
        $cleaned = array_filter($array, function ($val) {
            return is_string($val) || is_int($val);
        });

        if (!$caseSensitive) {
            $cleaned = array_map(function ($val) {
                if (is_string($val)) {
                    return strtolower($val);
                }
                return $val;
            }, $cleaned);
        }

        return array_count_values($cleaned);
    }
}
