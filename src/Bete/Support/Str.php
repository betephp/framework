<?php

namespace Bete\Support;

class Str
{
    public static function match($pattern, $value)
    {
        if ($pattern == $value) {
            return true;
        }

        $pattern = preg_quote($pattern, '#');
        
        $pattern = str_replace('\*', '.*', $pattern);

        return (bool) preg_match('#^'.$pattern.'\z#u', $value);
    }

    public static function snake($value)
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', $value);

            $value = preg_replace('/(.)(?=[A-Z])/u', '$1_', $value);

            $value = mb_strtolower($value, 'UTF-8');
        }

        return $value;
    }

    public static function startsWith($haystack, $needles)
    {
        $needles = (array) $needles;
        foreach ($needles as $needle) {
            if ($needle != '' && 
                substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    public static function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    public static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function replaceArray($search, array $replace, $subject)
    {
        foreach ($replace as $value) {
            $subject = static::replaceFirst($search, $value, $subject);
        }

        return $subject;
    }

    public static function replaceFirst($search, $replace, $subject)
    {
        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }
}
