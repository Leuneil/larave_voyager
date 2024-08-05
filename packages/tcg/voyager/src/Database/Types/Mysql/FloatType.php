<?php

namespace TCG\Voyager\Database\Types\Mysql;

use TCG\Voyager\Database\Types\Type;

class FloatType extends Type
{
    public const NAME = 'float';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $field)
    {
        return 'float';
    }
}
