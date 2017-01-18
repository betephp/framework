<?php

namespace Bete\Session;

use SessionHandlerInterface;

class FileHandler implements SessionHandlerInterface
{
    protected $path;

    protected $lifetime;

    public function __construct($path, $lifetime)
    {
        $this->path = $path;
        $this->lifetime = $lifetime;
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($sessionId)
    {
        $path = $this->path . '/' . $sessionId;

        if (file_exists($path)) {
            if (time() - filemtime($path) <= $this->lifetime) {
                return trim(file_get_contents($path));
            }
        }

        return '';
    }

    public function write($sessionId, $data)
    {
        $path = $this->path . '/' . $sessionId;

        file_put_contents($path , $data);
    }

    public function destroy($sessionId)
    {
        $path = $this->path . '/' . $sessionId;

        @unlink($path);
    }

    public function gc($lifetime)
    {
        $list = scandir($this->path);

        $time = time();
        if (is_array($list)) {
            foreach ($list as $item) {
                if (!Str::startsWith($item, '.')) {
                    $path = realpath($this->path . '/' . $item);
                    if ($time - filemtime($path) > $this->lifetime) {
                        @unlink($path);
                    }
                }
            }
        }
    }
}
