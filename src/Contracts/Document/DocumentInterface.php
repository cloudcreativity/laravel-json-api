<?php

namespace CloudCreativity\LaravelJsonApi\Contracts\Document;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;

interface DocumentInterface extends Arrayable, \JsonSerializable, Responsable
{
}
