<?php

namespace Bete\Encrypt;

use Bete\Exception\EncryptException;
use Bete\Support\Random;

class Encrypter
{
    protected $key;

    protected $cipher;

    public function __construct($key, $cipher = 'AES-256-CBC')
    {
        $key = (string) $key;

        if (!static::isSupported($key, $cipher)) {
            throw new EncryptException('The only supported ciphers are \
                AES-128-CBC and AES-256-CBC with the correct key lengths.');
        }

        $this->key = $key;
        $this->cipher = $cipher;
    }

    public static function isSupported($key, $cipher)
    {
        $length = mb_strlen($key, '8bit');

        return ($cipher === 'AES-128-CBC' && $length === 16) || ($cipher === 'AES-256-CBC' && $length === 32);
    }

    public function encrypt($value)
    {
        $iv = openssl_random_pseudo_bytes(16);

        $value = \openssl_encrypt(serialize($value), $this->cipher, 
            $this->key, 0, $iv);

        if ($value === false) {
            throw new EncryptException('Could not encrypt the data.');
        }

        $hash = $this->hash($iv = base64_encode($iv), $value);

        $json = json_encode(compact('iv', 'value', 'hash'));

        if (!is_string($json)) {
            throw new EncryptException('Could not encrypt the data.');
        }

        return base64_encode($json);
    }

    public function decrypt($data)
    {
        $data = $this->getJsonFromData($data);

        $iv = base64_decode($data['iv']);

        $decrypted = \openssl_decrypt($data['value'], $this->cipher, 
            $this->key, 0, $iv);

        if ($decrypted === false) {
            throw new EncryptException('Could not decrypt the data.');
        }

        return unserialize($decrypted);
    }

    protected function hash($iv, $value)
    {
        return hash_hmac('sha256', $iv.$value, $this->key);
    }

    protected function getJsonFromData($data)
    {
        $data = json_decode(base64_decode($data), true);

        if (! $this->validateData($data)) {
            throw new EncryptException('The data is invalid.');
        }

        if (! $this->validHash($data)) {
            throw new EncryptException('The MAC is invalid.');
        }

        return $data;
    }

    protected function validateData($data)
    {
        return is_array($data) && isset($data['iv'], $data['value'], 
            $data['hash']);
    }

    protected function validHash(array $data)
    {
        $bytes = Random::bytes(16);

        $calcMac = hash_hmac('sha256', $this->hash($data['iv'], 
            $data['value']), $bytes, true);

        return hash_equals(hash_hmac('sha256', $data['hash'], $bytes, true), 
            $calcMac);
    }

    public function getKey()
    {
        return $this->key;
    }
}
