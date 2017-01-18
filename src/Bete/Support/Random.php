<?php

namespace Bete\Support;

use Exception;
use Error;

class Random
{

    protected static $fh;

    public static function string($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = Random::bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', 
                base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    public static function bytes($bytes)
    {
        if ($bytes < 0) {
            throw new Exception('Length must be greater than 0');
        }

        if (function_exists('random_bytes')) {
            return random_bytes($bytes);
        }
        
        if (DIRECTORY_SEPARATOR === '/' && @is_readable('/dev/urandom')) {
            return static::urandom($bytes);
        }

        if (extension_loaded('mcrypt')) {
            return static::mcryptRandom($bytes);
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            return openssl_random_pseudo_bytes($bytes);
        }

        throw new Exception("Can not get random bytes.");
    }

    public static function urandom($bytes)
    {
        $fh = static::$fh;

        if (empty($fh)) {
            $fh = fopen('/dev/urandom', 'rb');
            if (!empty($fh)) {
                $st = fstat($fh);
                if (($st['mode'] & 0170000) !== 020000) {
                    fclose($fh);
                    $fh = false;
                }
            }
        }

        if (!empty($fh)) {
            $remaining = $bytes;
            $buf = '';

            do {
                $read = fread($fh, $remaining); 
                if ($read === false) {
                    $buf = false;
                    break;
                }

                $remaining -= mb_strlen($read, '8bit');
                $buf .= $read;
            } while ($remaining > 0);
            
            if ($buf !== false) {
                if (mb_strlen($buf, '8bit') === $bytes) {
                    return $buf;
                }
            }
        }

        return false;
    }


    protected static function mcryptRandom($bytes)
    {
        $buf = @mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
        if ($buf !== false && mb_strlen($buf, '8bit') === $bytes) {
            return $buf;
        }

        return false;
    }

}
