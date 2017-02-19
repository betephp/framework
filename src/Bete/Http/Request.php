<?php

namespace Bete\Http;

use Bete\Foundation\Application;

class Request
{

    protected $app;

    protected $route;

    protected $pathInfo;

    protected $requestUri;

    protected $scriptUrl;

    protected $baseUrl;

    protected $acceptsJson;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function pathInfo()
    {
        if ($this->pathInfo === null) {
            $this->pathInfo = $this->resolvePathInfo();
        }
        return $this->pathInfo;
    }

    protected function resolvePathInfo()
    {
        $pathInfo = $this->getUrl(); 
        if (($pos = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }

        $scriptUrl = $this->scriptUrl();
        $baseUrl = $this->baseUrl();

        if (strpos($pathInfo, $scriptUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($scriptUrl));
        } elseif ($baseUrl === '' || strpos($pathInfo, $baseUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($baseUrl));
        } else {
            throw new Exception("Error Processing Request", 1);
        }

        if (substr($pathInfo, 0, 1) === '/') {
            $pathInfo = substr($pathInfo, 1);
        }

        return (string) $pathInfo;
    }

    public function scriptUrl()
    {
        if ($this->scriptUrl === null) {
            $scriptFile = $this->scriptFile();
            $scriptName = basename($scriptFile);

            if (isset($_SERVER['SCRIPT_NAME']) && 
                basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->scriptUrl = $_SERVER['SCRIPT_NAME'];
            } else {
                throw new Exception("Error");
            }
        }
        return $this->scriptUrl;
    }

    public function baseUrl()
    {
        if ($this->baseUrl === null) {
            $this->baseUrl = rtrim(dirname($this->scriptUrl), '\\/');
        }
        return $this->baseUrl;
    }

    public function scriptFile()
    {
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            return $_SERVER['SCRIPT_FILENAME'];
        } else {
            throw new Exception("Can not resolve script filename.");
        }
    }

    public function getUrl()
    {
        if ($this->requestUri === null) {
            if (isset($_SERVER['REQUEST_URI'])) {
                $this->requestUri = $_SERVER['REQUEST_URI'];
            }
        }
        return $this->requestUri;
    }

    public function get($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $_GET;
        } else {
            return isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
        }
    }

    public function post($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $_POST;
        } else {
            return isset($_POST[$name]) ? $_POST[$name] : $defaultValue;
        }
    }

    public function isGet()
    {
        return $this->getMethod() === 'GET';
    }

    public function isPost()
    {
        return $this->getMethod() === 'POST';
    }

    public function getMethod()
    {   
        if (isset($_SERVER['REQUEST_METHOD'])) {
            return strtoupper($_SERVER['REQUEST_METHOD']);
        }
        
        return 'GET';
    }

    public function getReferrer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? 
            $_SERVER['HTTP_REFERER'] : null;
    }

    public function getProtocal()
    {
        if (isset($_SERVER['SERVER_PROTOCOL']) 
            && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0') {
            return 'HTTP/1.0';
        } else {
            return 'HTTP/1.1';
        }
    }

    public function acceptHeader()
    {
        return isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    }

    public function getContentTypesFromString($accepts)
    {
        if (empty($accepts)) {
            return [];
        }

        $accepts .= ',';
        $accepts = preg_split('/\s*(;.*?)*,\s*/', $accepts, 
            0, PREG_SPLIT_NO_EMPTY);

        return array_unique($accepts);
    }

    public function getAcceptableContentTypes()
    {
        $accepts = $this->acceptHeader();
        return $this->getContentTypesFromString($accepts);
    }

    public function accepts($contentType, $strict = false)
    {
        $accepts = $this->getAcceptableContentTypes();

        if (count($accepts) === 0) {
            return true;
        }

        foreach ($accepts as $accept) {
            if (!$strict) {
                if ($accept === '*/*' || $accepts === '*') {
                    return true;
                }
            }

            if ($accept === $contentType) {
                return true;
            }
        }

        return false;
    }

    public function acceptsJson()
    {
        if ($this->acceptsJson) {
            return true;
        }

        return $this->accepts("application/json", true);
    }

    public function setAcceptsJson($boolean)
    {
        $this->acceptsJson = $boolean;
    }

    public function acceptsHtml()
    {
        return $this->accepts("text/html");
    }

}

