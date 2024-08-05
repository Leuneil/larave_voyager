<?php

namespace TCG\Voyager\Database\Types\Mysql;

use TCG\Voyager\Database\Types\Type;

class TinyTextType extends Type
{
    public const NAME = 'tinytext';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $field)
    {
        return 'tinytext';
    }
}
