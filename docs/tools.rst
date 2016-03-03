Tools
=====

Helper classes for performing common tasks.

.. php:class:: ArrayHelper

    collection of static helper functions for working with arrays

    .. php:method:: countValues(array $array, $caseSensitive = true)

    A wrapper around ``array_count_values()`` that cleans the array values first, and allows to ignore string case.

.. php:class:: StringHelper

    collection of static helper functions for working with strings
    
    .. php:const:: BRACKET_SQUARE   = '[]'
    .. php:const:: BRACKET_ROUND    = '()'
    .. php:const:: BRACKET_CURLY    = '{}'
    .. php:const:: BRACKET_CHEVRON  = '<>'

    .. php:method:: canonicalize($string, $toLowerCase = true, $allowDash = false)

    cleans a string, leaving only alphanumeric values, and optionally allow dashes.

    :param string $string: input string
    :param bool $toLowerCase: transform string to lowercase
    :param bool $allowDash: remove dashes
    :returns: the canonicalized string

    .. php:method:: bracesToArray($string, $braces = self::BRACKET_ROUND, $first = true)

    explodes strings into arrays based on braces.

    .. code-block:: php
       
        $string = "(my)(string(value))";
        $array = StringHelper::bracesToArray($string);
        
        // $array now contains:
        [
            ["my"],
            [
                ["string"],
                [
                    ["value"]
                ]
            ]
        ]
