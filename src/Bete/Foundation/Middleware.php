<?php

namespace Bete\Foundation;

class Middleware
{
    protected $params;

    public function setParams($params)
    {
        $i = 1;
        foreach ($params as $param) {
            $this->params[$i] = $param;
            $i++;
        }
    }

    public function getParam($num = null, $defaultValue = null)
    {
        if ($num == null) {
            return $this->params;
        }

        return isset($this->params[$num]) ? $this->params[$num] : $defaultValue;
    }

    public function beforeAction($action)
    {
        return true;
    }

    public function afterAction($action, $result)
    {
        return $result;
    }
}
