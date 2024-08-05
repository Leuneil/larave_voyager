<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class BigIntType extends Type
{
    public const NAME = 'bigint';

    public function getSQLDeclaration(array $field)
    {
        return 'bigint';
    }
}
