<?php

namespace Bete\Validation;

use DateTime;
use Bete\Foundation\Application;
use Bete\Exception\ValidationException;
use Bete\Support\Str;
use Bete\Support\Arr;

class Validator
{
    protected $initialRules;

    protected $rules;

    protected $messageRepository;

    public $messages;

    protected $customMessages;

    protected $customAttributes;

    protected $sizeRules = ['Size', 'Between', 'Min', 'Max'];

    protected $numericRules = ['Numeric', 'Integer'];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $this->parseData($data);
        $this->initialRules = $rules;
        $this->customMessages = $messages;

        $this->prepareRules();
    }

    protected function parseData($data)
    {
        return $data;
    }

    protected function prepareRules()
    {
        $rules = [];

        foreach ($this->initialRules as $attribute => $rule) {
            $rule = is_string($rule) ? explode('|', $rule) : (array) $rule;
            foreach ($rule as $key => $value) {
                $value = explode(':', $value);
                if (count($value) == 2 && $value[0] == 'name') {
                    $this->customAttributes[$attribute] = $value[1];
                    unset($rule[$key]);
                }
            }
            $rules[$attribute] = array_values($rule);
        }

        $this->rules = $rules;
    }

    public function validate()
    {
        if ($this->fails()) {
            throw new ValidationException($this->messages->all());
        }
    }

    public function fails()
    {
        return !$this->passes();
    }

    public function passes()
    {
        $this->messageRepository = new MessageRepository();
        $this->messages = new MessageBag();

        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                if(!$this->validateAttribute($attribute, $rule)) {
                    break;
                }
            }
        }

        return $this->messages->isEmpty();
    }

    public function validateAttribute($attribute, $rule)
    {
        list($rule, $parameters) = $this->parseRule($rule);

        if ($rule == '') {
            return true;
        }

        $value = $this->getValue($attribute);

        $validatable = $this->isValidatable($rule, $attribute, $value);

        $method = "validate{$rule}";

        if ($validatable && !$this->$method($attribute, $value, $parameters)) {
            $this->setError($attribute, $rule, $parameters);
            return false;
        }

        return true;
    }

    protected function isValidatable($rule, $attribute, $value)
    {
        if ($this->hasRule($attribute, 'Required')) {
            return true;
        } else {
            return (!is_null($value));
        }
    }

    public function setError($attribute, $rule, $parameters)
    {
        $message = $this->getMessage($attribute, $rule);

        $message = $this->doReplacements($message, $attribute, $rule,
            $parameters);

        $this->messages->set($attribute, $message);
    }

    protected function getValue($attribute)
    {
        return isset($this->data[$attribute]) ? $this->data[$attribute] : null;
    }

    protected function hasRule($attribute, $rules)
    {
        return !is_null($this->getRule($attribute, $rules));
    }

    protected function getRule($attribute, $rules)
    {
        if (!array_key_exists($attribute, $this->rules)) {
            return;
        }

        $rules = (array) $rules;

        foreach ($this->rules[$attribute] as $rule) {
            list($rule, $parameters) = $this->parseRule($rule); 

            if (in_array($rule, $rules)) {
                return [$rule, $parameters];
            }
        }
    }
    protected function parseRule($rule)
    {
        $rule = $this->parseStringRule($rule);

        $rule[0] = $this->normalizeRule($rule[0]);

        return $rule;
    }

    protected function normalizeRule($rule)
    {
        switch ($rule) {
            case 'Int':
                return 'Integer';
            case 'Bool':
                return 'Boolean';
            case 'Num':
                return 'Numeric';
            case 'Str':
                return 'String';
            default:
                return $rule;
        }
    }

    public function parseStringRule($rule)
    {
        $parameters = [];

        if (strpos($rule, ':') !== false) {
            list($rule, $parameter) = explode(':', $rule, 2);

            $parameters = $this->parseParameters($rule, $parameter);
        }

        $rule = $this->convertRuleName($rule);

        return [$rule, $parameters];
    }

    public function convertRuleName($value)
    {
        $value = ucwords(str_replace('_', ' ', $value));

        return str_replace(' ', '', $value);
    }

    protected function parseParameters($rule, $parameter)
    {
        if (strtolower($rule) == 'regex') {
            return [$parameter];
        }

        return str_getcsv($parameter);
    }


    protected function validateRequired($attribute, $value)
    {
        return array_key_exists($attribute, $this->data);
    }

    protected function validateSize($attribute, $value, $parameters)
    {
        return $this->getSize($attribute, $value) == $parameters[0];
    }

    protected function getSize($attribute, $value)
    {
        $isNumeric = $this->hasRule($attribute, $this->numericRules);

        if (is_numeric($value) && $isNumeric) {
            return $value;
        } elseif (is_array($value)) {
            return count($value);
        }

        return mb_strlen($value);
    }

    protected function validateBetween($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'between');

        $size = $this->getSize($attribute, $value);

        return $size >= $parameters[0] && $size <= $parameters[1];
    }

    protected function validateSame($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'same');

        $other = Arr::get($this->data, $parameters[0]);

        return isset($other) && $value === $other;
    }

    protected function validateDifferent($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'different');

        $other = Arr::get($this->data, $parameters[0]);

        return isset($other) && $value !== $other;
    }

    protected function validateMax($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'max');

        return $this->getSize($attribute, $value) <= $parameters[0];
    }

    protected function validateMin($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'min');

        return $this->getSize($attribute, $value) >= $parameters[0];
    }

    protected function validateIn($attribute, $value, $parameters)
    {
        if (is_array($value) && $this->hasRule($attribute, 'Array')) {
            foreach ($value as $element) {
                if (is_array($element)) {
                    return false;
                }
            }

            return count(array_diff($value, $parameters)) == 0;
        }

        return !is_array($value) && in_array((string) $value, $parameters);
    }

    protected function validateNotIn($attribute, $value, $parameters)
    {
        return !$this->validateIn($attribute, $value, $parameters);
    }



    protected function validateAlpha($attribute, $value)
    {
        return is_string($value) && ctype_alpha($value);
    }

    protected function validateAlnum($attribute, $value)
    {
        return is_string($value) && ctype_alnum($value);
    }

    protected function validateAlnumDash($attribute, $value)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9_]+$/u', $value) > 0;
    }

    protected function validateArray($attribute, $value)
    {
        return is_array($value);
    }

    protected function validateBoolean($attribute, $value)
    {
        $values = [true, false, 0, 1, '0', '1'];

        return in_array($value, $values, true);
    }

    protected function validateDigits($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'digits');

        return !preg_match('/[^0-9]/', $value)
            && strlen((string) $value) == $parameters[0];
    }

    protected function validateDigitsBetween($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'digits_between');

        $length = strlen((string) $value);

        return !preg_match('/[^0-9]/', $value)
          && $length >= $parameters[0] && $length <= $parameters[1];
    }

    protected function validateInteger($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateNumeric($attribute, $value)
    {
        return is_numeric($value);
    }

    protected function validateString($attribute, $value)
    {
        return is_string($value);
    }

    protected function validateUrl($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        /*
         * This pattern is derived from Symfony\Component\Validator\Constraints\UrlValidator (2.7.4).
         *
         * (c) Fabien Potencier <fabien@symfony.com> http://symfony.com
         */
        $pattern = '~^
            ((aaa|aaas|about|acap|acct|acr|adiumxtra|afp|afs|aim|apt|attachment|aw|barion|beshare|bitcoin|blob|bolo|callto|cap|chrome|chrome-extension|cid|coap|coaps|com-eventbrite-attendee|content|crid|cvs|data|dav|dict|dlna-playcontainer|dlna-playsingle|dns|dntp|dtn|dvb|ed2k|example|facetime|fax|feed|feedready|file|filesystem|finger|fish|ftp|geo|gg|git|gizmoproject|go|gopher|gtalk|h323|ham|hcp|http|https|iax|icap|icon|im|imap|info|iotdisco|ipn|ipp|ipps|irc|irc6|ircs|iris|iris.beep|iris.lwz|iris.xpc|iris.xpcs|itms|jabber|jar|jms|keyparc|lastfm|ldap|ldaps|magnet|mailserver|mailto|maps|market|message|mid|mms|modem|ms-help|ms-settings|ms-settings-airplanemode|ms-settings-bluetooth|ms-settings-camera|ms-settings-cellular|ms-settings-cloudstorage|ms-settings-emailandaccounts|ms-settings-language|ms-settings-location|ms-settings-lock|ms-settings-nfctransactions|ms-settings-notifications|ms-settings-power|ms-settings-privacy|ms-settings-proximity|ms-settings-screenrotation|ms-settings-wifi|ms-settings-workplace|msnim|msrp|msrps|mtqp|mumble|mupdate|mvn|news|nfs|ni|nih|nntp|notes|oid|opaquelocktoken|pack|palm|paparazzi|pkcs11|platform|pop|pres|prospero|proxy|psyc|query|redis|rediss|reload|res|resource|rmi|rsync|rtmfp|rtmp|rtsp|rtsps|rtspu|secondlife|service|session|sftp|sgn|shttp|sieve|sip|sips|skype|smb|sms|smtp|snews|snmp|soap.beep|soap.beeps|soldat|spotify|ssh|steam|stun|stuns|submit|svn|tag|teamspeak|tel|teliaeid|telnet|tftp|things|thismessage|tip|tn3270|turn|turns|tv|udp|unreal|urn|ut2004|vemmi|ventrilo|videotex|view-source|wais|webcal|ws|wss|wtai|wyciwyg|xcon|xcon-userid|xfire|xmlrpc\.beep|xmlrpc.beeps|xmpp|xri|ymsgr|z39\.50|z39\.50r|z39\.50s))://                                 # protocol
            (([\pL\pN-]+:)?([\pL\pN-]+)@)?          # basic auth
            (
                ([\pL\pN\pS-\.])+(\.?([\pL]|xn\-\-[\pL\pN-]+)+\.?) # a domain name
                    |                                              # or
                \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                 # an IP address
                    |                                              # or
                \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                \]  # an IPv6 address
            )
            (:[0-9]+)?                              # a port (optional)
            (/?|/\S+|\?\S*|\#\S*)                   # a /, nothing, a / with something, a query or a fragment
        $~ixu';

        return preg_match($pattern, $value) > 0;
    }

    protected function validateEmail($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateChinese($attribute, $value)
    {
        return preg_match("/^[\x7f-\xff]+$/", $value);
    }

    protected function validateIdCard($attribute, $value)
    {
        if (!preg_match('/^\d{17}[0-9xX]$/', $value)) {
            return false;
        }

        $parsed = date_parse(substr($value, 6, 8));
        if (!(isset($parsed['warning_count']) 
            && $parsed['warning_count'] == 0)) {
            return false;
        }

        $base = substr($value, 0, 17);

        $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];

        $tokens = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];

        $checkSum = 0;
        for ($i=0; $i<17; $i++) {
            $checkSum += intval(substr($base, $i, 1)) * $factor[$i];
        }

        $mod = $checkSum % 11;
        $token = $tokens[$mod];

        $lastChar = strtoupper(substr($value, 17, 1));

        return ($lastChar === $token);
    }

    protected function validateIp($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    protected function validateJson($attribute, $value)
    {
        if (!is_scalar($value) && !method_exists($value, '__toString')) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function validateRegex($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'regex');

        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return preg_match($parameters[0], $value) > 0;
    }

    protected function validateMobile($attribute, $value, $parameters)
    {
        return preg_match('/^1[0-9]{10}$/', $value);
    }

    protected function validateDate($attribute, $value)
    {
        if ($value instanceof DateTime) {
            return true;
        }

        if ((!is_string($value) && !is_numeric($value)) ||
            strtotime($value) === false) {
            return false;
        }

        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    protected function validateDateFormat($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'date_format');

        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $parsed = date_parse_from_format($parameters[0], $value);

        return $parsed['warning_count'] === 0 && $parsed['error_count'] === 0;
    }

    protected function validateBefore($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'before');

        if (!is_string($value) || !is_numeric($value) ||
            $value instanceof DateTime) {
            return false;
        }

        if (!$date = $this->getDateTimestamp($parameters[0])) {
            $date = $this->getDateTimestamp($this->getValue($parameters[0]));
        }

        return $this->getDateTimestamp($value) < $date;

    }

    protected function validateAfter($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'before');

        if (!is_string($value) || !is_numeric($value) ||
            $value instanceof DateTime) {
            return false;
        }

        if (!$date = $this->getDateTimestamp($parameters[0])) {
            $date = $this->getDateTimestamp($this->getValue($parameters[0]));
        }

        return $this->getDateTimestamp($value) > $date;
    }

    protected function getDateTimestamp($value)
    {
        return $value instanceof DateTimeInterface ? 
            $value->getTimestamp() : strtotime($value);
    }


    protected function requireParameterCount($count, $parameters, $rule)
    {
        if (count($parameters) < $count) {
            throw new \Exception(
                "Validation rule $rule requires at least $count parameters.");
        }
    }

    protected function replaceBetween($message, $attribute, $rule, $parameters)
    {
        return str_replace([':min', ':max'], $parameters, $message);
    }

    protected function replaceDateFormat($message, $attribute, $rule, $parameters)
    {
        return str_replace(':format', $parameters[0], $message);
    }

    protected function replaceDifferent($message, $attribute, $rule, $parameters)
    {
        return $this->replaceSame($message, $attribute, $rule, $parameters);
    }

    protected function replaceDigits($message, $attribute, $rule, $parameters)
    {
        return str_replace(':digits', $parameters[0], $message);
    }
    
    protected function replaceDigitsBetween($message, $attribute, $rule, $parameters)
    {
        return $this->replaceBetween($message, $attribute, $rule, $parameters);
    }

    protected function replaceMin($message, $attribute, $rule, $parameters)
    {
        return str_replace(':min', $parameters[0], $message);
    }

    protected function replaceMax($message, $attribute, $rule, $parameters)
    {
        return str_replace(':max', $parameters[0], $message);
    }

    protected function replaceIn($message, $attribute, $rule, $parameters)
    {
        return str_replace(':values', implode(', ', $parameters), $message);
    }

    protected function replaceNotIn($message, $attribute, $rule, $parameters)
    {
        return $this->replaceIn($message, $attribute, $rule, $parameters);
    }

    protected function replaceSize($message, $attribute, $rule, $parameters)
    {
        return str_replace(':size', $parameters[0], $message);
    }

    protected function replaceSame($message, $attribute, $rule, $parameters)
    {
        return str_replace(':other', $this->getAttribute($parameters[0]), $message);
    }

    protected function replaceBefore($message, $attribute, $rule, $parameters)
    {
        if (! (strtotime($parameters[0]))) {
            return str_replace(':date', $this->getAttribute($parameters[0]), $message);
        }

        return str_replace(':date', $parameters[0], $message);
    }

    protected function replaceAfter($message, $attribute, $rule, $parameters)
    {
        return $this->replaceBefore($message, $attribute, $rule, $parameters);
    }

    protected function getAttribute($attribute)
    {
        if(isset($this->customAttributes[$attribute])) {
            return $this->customAttributes[$attribute];
        }

        return $attribute;
    }

    protected function getAttributeType($attribute)
    {
        if ($this->hasRule($attribute, $this->numericRules)) {
            return 'numeric';
        } elseif ($this->hasRule($attribute, ['Array'])) {
            return 'array';
        }

        return 'string';
    }

    protected function doReplacements($message, $attribute, $rule, $parameters)
    {
        $attribute = $this->getAttribute($attribute);

        $message = str_replace(':attribute', $attribute, $message);

        $replacer = "replace{$rule}";
        if (method_exists($this, $replacer)) {
            $message = $this->$replacer($message, $attribute, $rule, $parameters);
        }

        return $message;
    }

    protected function getMessage($attribute, $rule)
    {
        $lowerRule = Str::snake($rule);

        $inlineMessage = $this->getInlineMessage($attribute, $lowerRule);

        if (!is_null($inlineMessage)) {
            return $inlineMessage;
        }

        if (in_array($rule, $this->sizeRules)) {
            return $this->getSizeMessage($attribute, $lowerRule);
        }

        $key = "validation.{$lowerRule}";

        return $this->messageRepository->get($key);
    }

    protected function getInlineMessage($attribute, $lowerRule)
    {
        $keys = ["{$attribute}.{$lowerRule}", $lowerRule];

        $customMessages = $this->customMessages;

        foreach ($keys as $key) {
            foreach (array_keys($customMessages) as $customKey) {
                if (Str::match($customKey, $key)) {
                    return $customMessages[$customKey];
                }
            }
        }
    }

    protected function getSizeMessage($attribute, $rule)
    {
        $type = $this->getAttributeType($attribute);

        $key = "validation.{$rule}.{$type}";

        return $this->messageRepository->get($key);
    }
}
