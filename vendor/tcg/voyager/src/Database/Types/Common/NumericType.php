<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class NumericType extends Type
{
    public const NAME = 'numeric';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $field)
    {
        $field['precision'] = $field['precision'] ?? 8;
        $field['scale'] = $field['scale'] ?? 2;

        return "numeric({$field['precision']}, {$field['scale']})";
    }
}
