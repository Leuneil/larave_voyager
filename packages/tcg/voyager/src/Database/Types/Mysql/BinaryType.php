<?php

namespace TCG\Voyager\Database\Types\Mysql;

use TCG\Voyager\Database\Types\Type;

class BinaryType extends Type
{
    public const NAME = 'binary';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $field)
    {
        $field['length'] = $field['length'] ?? 255;

        return "binary({$field['length']})";
    }
}
