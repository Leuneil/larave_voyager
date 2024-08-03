<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class DoubleType extends Type
{
    public const NAME = 'double';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $field)
    {
        $field['precision'] = $field['precision'] ?? 8;
        $field['scale'] = $field['scale'] ?? 2;

        return "double({$field['precision']}, {$field['scale']})";
    }
}
