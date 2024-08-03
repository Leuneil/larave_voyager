<?php

namespace TCG\Voyager\Database\Types\Mysql;

use TCG\Voyager\Database\Types\Type;

class TinyIntType extends Type
{
    public const NAME = 'tinyint';

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

        return 'tinyint' . (!empty($attributes) ? ' ' . implode(' ', $attributes) : '');
    }
}
