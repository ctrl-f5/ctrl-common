<?php

namespace Ctrl\Common\Tools;

class StringHelper
{
    const BRACKET_SQUARE    = '[]';
    const BRACKET_ROUND     = '()';
    const BRACKET_CURLY     = '{}';
    const BRACKET_CHEVRON   = '<>';

    public static function canonicalize($string, $toLowerCase = true, $allowDash = false)
    {
        if ($toLowerCase) $string = strtolower($string);

        $allowed = 'a-zA-Z0-9';
        if ($allowDash) $allowed .= '-';

        return preg_replace("/[^$allowed]+/", "", $string);
    }

    public static function bracesToArray($string, $braces = self::BRACKET_ROUND, $first = true)
    {
        $OPEN       = $braces[0];
        $CLOSE      = $braces[1];
        $result     = array();
        $string     = trim($string);
        $len        = strlen($string);

        // search open
        $open = strpos($string, $OPEN);
        // no open? add offset > last
        if ($open === false) {
            $result[] = $string;
            return $result;
        }

        // open > offset? add offset => open
        if ($open > 0) {
            $result[] = trim(substr($string, 0, $open));
        }

        // search close
        $count = 0;
        $close = $open;
        for ($i = $open; $i < $len; $i++) {
            if ($string[$i] === $OPEN) {
                $count++;
            }
            if ($string[$i] === $CLOSE) {
                $count--;
                if ($count === 0) {
                    $close = $i;
                    break;
                }
            }
        }

        // add open to close
        $subExpr = self::bracesToArray(substr($string, $open + 1, $close - $open - 1), $braces);
        $result[] = $subExpr;

        // if there is more, process it
        if ($close !== $len - 1) {
            $result = array_merge($result, self::bracesToArray(substr($string, $close + 1), $braces, false));
        }

        // remove single elements == double braces
        if ($first && count($result) === 1 && count($result[0]) === 1) {
            return $result[0];
        }

        return $result;
    }
}
