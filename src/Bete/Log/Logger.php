<?php

namespace Bete\Log;

use Bete\Foundation\Application;
use Bete\Exception\ConfigException;

class Logger
{
    protected $app;

    protected $dateSuffix;

    protected $traceLevel;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function setDateSuffix($dateSuffix)
    {
        if (!preg_match('/^[YmdHi_\.]*$/', $dateSuffix)) {
            throw new ConfigException("The log date suffix is illegal.");
        }

        $this->dateSuffix = $dateSuffix;
    }

    public function setTraceLevel($traceLevel)
    {
        $this->traceLevel = $traceLevel;
    }

    public function error($message, $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    public function warning($message, $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    public function notice($message, $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    public function info($message, $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    public function debug($message, $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    protected function log($level, $message, array $context = array())
    {
        return $this->writeLog($level, $message, $context);
    }

    public function writeLog($level, $message, array $context)
    {
        $logFile = $this->getLogFile();
        $dateTime = date("Y-m-d H:i:s");
        $level = strtoupper($level);
        $message = $this->formatMessage($message);
        $context = empty($context) ? '[]' : json_encode($context);
        $traces = $this->getTraces();

        $text = "[{$dateTime}] {$level}: {$message} {$context}" .
            (empty($traces) ? '' : "\n    " . implode("\n    ", $traces));
        $text .= "\n";

        if (($fh = @fopen($logFile, 'a')) === false) {
            throw new ConfigException(
                "Unable to append to log file: {$logFile}");
        }
        
        @flock($fp, LOCK_EX);
        @fwrite($fh, $text);
        @flock($fh, LOCK_UN);
        @fclose($fh);

        return true;
    }

    public function getLogFile()
    {
        if (empty($this->dateSuffix)) {
            $fileName = 'app.log';
        } else {
            $fileName = 'app.log.' . date("{$this->dateSuffix}");
        }

        return $this->app['path.log'] . DIRECTORY_SEPARATOR . $fileName;
    }

    public function data($message, array $context = array())
    {
        return $this->writeData($message, $context);
    }

    public function writeData($message, array $context)
    {
        $logFile = $this->getDataFile();
        $dateTime = date("Y-m-d H:i:s");
        $context = empty($context) ? '[]' : json_encode($context);

        $text = "[{$dateTime}] DATA: {$message} {$context}\n";

        if (($fh = @fopen($logFile, 'a')) === false) {
            throw new ConfigException(
                "Unable to append to log file: {$logFile}");
        }
        
        @flock($fp, LOCK_EX);
        @fwrite($fh, $text);
        @flock($fh, LOCK_UN);
        @fclose($fh);

        return true;
    }

    protected function getDataFile()
    {
        if (empty($this->dateSuffix)) {
            $fileName = 'app.dat';
        } else {
            $fileName = 'app.dat.' . date("{$this->dateSuffix}");
        }

        return $this->app['path.log'] . DIRECTORY_SEPARATOR . $fileName;
    }

    public function formatMessage($message)
    {
        switch (gettype($message)) {
            case 'boolean':
                return $message ? 'true' : 'false';
                break;
            case 'integer':
                return $message;
                break;
            case 'double':
                return $message;
                break;
            case 'string':
                return "'" . addslashes($message) . "'";
                break;
            case 'resource':
                return '{resource}';
                break;
            case 'NULL':
                return 'null';
                break;
            case 'unknown type':
                return '{unknown}';
                break;
            case 'array':
                return json_decode($message);
                break;
            case 'object':
                if (method_exists($message, '__toString')) {
                    return (string) $message;
                } else {
                    $className = get_class($message);
                    return "class[{$className}]";
                }
            default:
                return 'unknown';
                break;
        }
    }

    public function getTraces()
    {
        $traces = [];
        if ($this->traceLevel > 0) {
            $count = 0;
            $traceList = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            foreach ($traceList as $trace) {
                if (isset($trace['file'], $trace['line'])) {
                    $traces[] = "in {$trace['file']}:{$trace['line']}";
                    if (++$count >= $this->traceLevel) {
                        break;
                    }
                }
            }
        }

        return $traces;
    }

}
