<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class VarCharType extends Type
{
    public const NAME = 'varchar';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $field)
    {
        $field['length'] = empty($field['length']) ? 255 : $field['length'];

        return "varchar({$field['length']})";
    }
}
