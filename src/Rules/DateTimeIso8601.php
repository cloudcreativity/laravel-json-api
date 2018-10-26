<?php

namespace CloudCreativity\LaravelJsonApi\Rules;

use DateTime;
use Illuminate\Contracts\Validation\Rule;

class DateTimeIso8601 implements Rule
{

    /**
     * @inheritDoc
     */
    public function passes($attribute, $value)
    {
        if (!is_string($value) || empty($value)) {
            return false;
        }

        return collect([
            'Y-m-d\TH:iP',
            'Y-m-d\TH:i:sP',
            'Y-m-d\TH:i:s.uP',
        ])->contains(function ($format) use ($value) {
            return $this->accept($value, $format);
        });
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return trans("jsonapi::validation.date_time_iso_8601");
    }

    /**
     * @param string $value
     * @param string $format
     * @return bool
     */
    private function accept(string $value, string $format): bool
    {
        $date = DateTime::createFromFormat($format, $value);

        return $date instanceof DateTime;
    }

}
