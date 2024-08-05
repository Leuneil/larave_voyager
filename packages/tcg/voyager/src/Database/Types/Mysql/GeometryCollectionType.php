<?php

namespace TCG\Voyager\Database\Types\Mysql;

use TCG\Voyager\Database\Types\Type;

class GeometryCollectionType extends Type
{
    public const NAME = 'geometrycollection';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $field)
    {
        return 'geometrycollection';
    }
}
