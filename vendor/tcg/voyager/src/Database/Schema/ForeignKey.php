<?php

namespace TCG\Voyager\Database\Schema;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

abstract class ForeignKey
{
    public static function make(array $foreignKey)
    {
        // Set the local table
        $localTable = $foreignKey['localTable'] ?? null;

        $localColumns = $foreignKey['localColumns'] ?? [];
        $foreignTable = $foreignKey['foreignTable'] ?? null;
        $foreignColumns = $foreignKey['foreignColumns'] ?? [];
        $options = $foreignKey['options'] ?? [];

        // Set the name
        $name = isset($foreignKey['name']) ? trim($foreignKey['name']) : '';
        if (empty($name)) {
            $name = self::createName($localColumns, $foreignTable, $foreignColumns);
        } else {
            $name = Identifier::validate($name, 'Foreign Key');
        }

        return [
            'name' => $name,
            'localTable' => $localTable,
            'localColumns' => $localColumns,
            'foreignTable' => $foreignTable,
            'foreignColumns' => $foreignColumns,
            'options' => $options
        ];
    }

    /**
     * @return array
     */
    public static function toArray(array $foreignKey)
    {
        return [
            'name'           => $foreignKey['name'] ?? '',
            'localTable'     => $foreignKey['localTable'] ?? '',
            'localColumns'   => $foreignKey['localColumns'] ?? [],
            'foreignTable'   => $foreignKey['foreignTable'] ?? '',
            'foreignColumns' => $foreignKey['foreignColumns'] ?? [],
            'options'        => $foreignKey['options'] ?? [],
        ];
    }

    protected static function createName($localColumns, $foreignTable, $foreignColumns)
    {
        return 'fk_' . implode('_', $localColumns) . '_' . $foreignTable . '_' . implode('_', $foreignColumns);
    }

    public static function apply(Blueprint $table, array $foreignKey)
    {
        $name = $foreignKey['name'];
        $localColumns = $foreignKey['localColumns'];
        $foreignTable = $foreignKey['foreignTable'];
        $foreignColumns = $foreignKey['foreignColumns'];
        $options = $foreignKey['options'];

        $foreignKeyConstraint = $table->foreign($localColumns)
            ->references($foreignColumns)
            ->on($foreignTable)
            ->name($name);

        if (isset($options['onDelete'])) {
            $foreignKeyConstraint->onDelete($options['onDelete']);
        }

        if (isset($options['onUpdate'])) {
            $foreignKeyConstraint->onUpdate($options['onUpdate']);
        }
    }
}
