<?php

namespace Bete\Validation;

use Bete\Support\Arr;

class MessageRepository
{
    public function get($key)
    {
        $messages = $this->resolveMessages();

        return Arr::get($messages, $key);
    }

    public function resolveMessages()
    {
        $locale = app()->config['app.locale'];

        $file = realpath(app()->configPath()). "/lang/{$locale}/validation.php";

        if (file_exists($file)) {
            $messages = require($file);
        } else {
            $messages = $this->messages;
        }

        return ['validation' => $messages];
    }

    protected $messages = [
        'after'                => 'The :attribute must be a date after :date.',
        'alpha'                => 'The :attribute may only contain letters.',
        'alnum'                => 'The :attribute may only contain letters and numbers.',
        'alnum_dash'           => 'The :attribute may only contain letters, numbers, and dashes.',
        'array'                => 'The :attribute must be an array.',
        'before'               => 'The :attribute must be a date before :date.',
        'between'              => [
            'numeric' => 'The :attribute must be between :min and :max.',
            'string'  => 'The :attribute must be between :min and :max characters.',
            'array'   => 'The :attribute must have between :min and :max items.',
        ],
        'boolean'              => 'The :attribute field must be true or false.',
        'chinese'              => 'The :attribute field must only contain chinese.',
        'date'                 => 'The :attribute is not a valid date.',
        'date_format'          => 'The :attribute does not match the format :format.',
        'different'            => 'The :attribute and :other must be different.',
        'digits'               => 'The :attribute must be :digits digits.',
        'digits_between'       => 'The :attribute must be between :min and :max digits.',
        'email'                => 'The :attribute must be a valid email address.',
        'id_card'              => 'The :attribute must be a valid chinese id number.',
        'in'                   => 'The selected :attribute is invalid.',
        'integer'              => 'The :attribute must be an integer.',
        'ip'                   => 'The :attribute must be a valid IP address.',
        'json'                 => 'The :attribute must be a valid JSON string.',
        'max'                  => [
            'numeric' => 'The :attribute may not be greater than :max.',
            'string'  => 'The :attribute may not be greater than :max characters.',
            'array'   => 'The :attribute may not have more than :max items.',
        ],
        'min'                  => [
            'numeric' => 'The :attribute must be at least :min.',
            'string'  => 'The :attribute must be at least :min characters.',
            'array'   => 'The :attribute must have at least :min items.',
        ],
        'mobile'               => 'The :attribute must be a valid chinese mobile number',
        'not_in'               => 'The selected :attribute is invalid.',
        'numeric'              => 'The :attribute must be a number.',
        'regex'                => 'The :attribute format is invalid.',
        'required'             => 'The :attribute field is required.',
        'same'                 => 'The :attribute and :other must match.',
        'size'                 => [
            'numeric' => 'The :attribute must be :size.',
            'string'  => 'The :attribute must be :size characters.',
            'array'   => 'The :attribute must contain :size items.',
        ],
        'string'               => 'The :attribute must be a string.',
        'url'                  => 'The :attribute format is invalid.',
    ];
}
