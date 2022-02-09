<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
