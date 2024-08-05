<?php

namespace TCG\Voyager\Database\Types\Mysql;

use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Types\Type;

class EnumType extends Type
{
    public const NAME = 'enum';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $field)
    {
        if (empty($field['allowed'])) {
            throw new \Exception('Enum type requires allowed values.');
        }

        $pdo = DB::connection()->getPdo();

        // Trim and quote the values
        $allowed = array_map(function ($value) use ($pdo) {
            return $pdo->quote(trim($value));
        }, $field['allowed']);

        return 'enum(' . implode(', ', $allowed) . ')';
    }
}
