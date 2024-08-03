<?php

namespace TCG\Voyager\Database\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

abstract class Index
{
    public const PRIMARY = 'PRIMARY';
    public const UNIQUE = 'UNIQUE';
    public const INDEX = 'INDEX';

    public static function make(array $index)
    {
        $columns = $index['columns'];
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        if (isset($index['type'])) {
            $type = $index['type'];

            $isPrimary = ($type == static::PRIMARY);
            $isUnique = $isPrimary || ($type == static::UNIQUE);
        } else {
            $isPrimary = $index['isPrimary'] ?? false;
            $isUnique = $index['isUnique'] ?? false;

            // Set the type
            if ($isPrimary) {
                $type = static::PRIMARY;
            } elseif ($isUnique) {
                $type = static::UNIQUE;
            } else {
                $type = static::INDEX;
            }
        }

        // Set the name
        $name = trim($index['name'] ?? '');
        if (empty($name)) {
            $table = $index['table'] ?? null;
            $name = static::createName($columns, $type, $table);
        } else {
            $name = Identifier::validate($name, 'Index');
        }

        $flags = $index['flags'] ?? [];
        $options = $index['options'] ?? [];

        return [
            'name' => $name,
            'columns' => $columns,
            'isUnique' => $isUnique,
            'isPrimary' => $isPrimary,
            'flags' => $flags,
            'options' => $options,
        ];
    }

    /**
     * @return array
     */
    public static function toArray(array $index)
    {
        $name = $index['name'];
        $columns = $index['columns'];

        return [
            'name'        => $name,
            'oldName'     => $name,
            'columns'     => $columns,
            'type'        => static::getType($index),
            'isPrimary'   => $index['isPrimary'] ?? false,
            'isUnique'    => $index['isUnique'] ?? false,
            'isComposite' => count($columns) > 1,
            'flags'       => $index['flags'] ?? [],
            'options'     => $index['options'] ?? [],
        ];
    }

    public static function getType(array $index)
    {
        if ($index['isPrimary'] ?? false) {
            return static::PRIMARY;
        } elseif ($index['isUnique'] ?? false) {
            return static::UNIQUE;
        } else {
            return static::INDEX;
        }
    }

    /**
     * Create a default index name.
     *
     * @param array  $columns
     * @param string $type
     * @param string $table
     *
     * @return string
     */
    public static function createName(array $columns, $type, $table = null)
    {
        $table = isset($table) ? trim($table).'_' : '';
        $type = trim($type);
        $name = strtolower($table.implode('_', $columns).'_'.$type);

        return str_replace(['-', '.'], '_', $name);
    }

    public static function availableTypes()
    {
        return [
            static::PRIMARY,
            static::UNIQUE,
            static::INDEX,
        ];
    }

    public static function apply(Blueprint $table, array $index)
    {
        $name = $index['name'];
        $columns = $index['columns'];
        $isUnique = $index['isUnique'];
        $isPrimary = $index['isPrimary'];

        if ($isPrimary) {
            $table->primary($columns, $name);
        } elseif ($isUnique) {
            $table->unique($columns, $name);
        } else {
            $table->index($columns, $name);
        }
    }
}
