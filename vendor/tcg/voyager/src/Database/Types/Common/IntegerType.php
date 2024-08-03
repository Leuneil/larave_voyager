<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class IntegerType extends Type
{
    public const NAME = 'integer';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $field)
    {
        $attributes = [];

        if (isset($field['unsigned']) && $field['unsigned']) {
            $attributes[] = 'unsigned';
        }

        if (isset($field['autoIncrement']) && $field['autoIncrement']) {
            $attributes[] = 'auto_increment';
        }

        return 'integer' . (!empty($attributes) ? ' ' . implode(' ', $attributes) : '');
    }
}
