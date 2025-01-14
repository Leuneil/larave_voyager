<?php

namespace TCG\Voyager\Database\Types\Mysql;

use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Types\Type;

class SetType extends Type
{
    public const NAME = 'set';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        if (empty($fieldDeclaration['comment'])) {
            throw new \Exception('Set type is not supported');
        }

        // we're going to store SET values in the comment since DBAL doesn't support
        $allowed = explode(',', trim($fieldDeclaration['comment']));

        $pdo = DB::connection()->getPdo();

        // trim the values
        $fieldDeclaration['allowed'] = array_map(function ($value) use ($pdo) {
            return $pdo->quote(trim($value));
        }, $allowed);

        return 'set(' . implode(', ', $fieldDeclaration['allowed']) . ')';
    }
}
