<?php

namespace TCG\Voyager\Database\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;

class Column
{
    public static function make(array $column, Blueprint $tableBuilder, $creating = false, $afterColumn = null)
    {
    
        if (!isset($column['name'])) {
            throw new \Exception("Column name is required.");
        }
    
        $column['name'] = self::normalizeColumnName($column['name']);
        $column['type'] = self::normalizeColumnType($column['type']);
    
        $name = trim($column['name']);
        $type = strtolower(trim($column['type'])); 
    
        $name = Identifier::validate($name, 'Column');
    
        $options = Arr::except($column, ['name', 'type']);
        $options['name'] = $name;
        $options['type'] = $type;
    
        // Determine the correct method based on auto_increment and type
        $method = self::getColumnTypeMethod($type, $options);
    
        // Create or modify the column with appropriate method
        $columnDefinition = $tableBuilder->{$method}($name, $options['length'] ?? null);
    
        // Apply the unsigned modifier directly
        if ($options['unsigned']) {
            $columnDefinition->unsigned();
        }
        
    
        // Apply other modifiers
        $columnDefinition = self::applyModifiers($columnDefinition, $options);
    
        // Apply the 'after' option if specified
        if ($afterColumn && $creating) {
            $columnDefinition->after($afterColumn);
        }
    
        // Finalize the column definition with change method
        if (!$creating) {
            $columnDefinition->change();
        }
    
        return $columnDefinition;
    }

    protected static function getColumnTypeMethod($type, $options)
    {
        $type = strtolower($type);
        $isAutoIncrement = $options['autoincrement'] ?? false;

        $mapping = [
            'bigint' => $isAutoIncrement ? 'bigIncrements' : 'bigInteger',
            'integer' => $isAutoIncrement ? 'increments' : 'integer',
            'mediumint' => $isAutoIncrement ? 'mediumIncrements' : 'mediumInteger',
            'smallint' => $isAutoIncrement ? 'smallIncrements' : 'smallInteger',
            'tinyint' => $isAutoIncrement ? 'tinyIncrements' : 'tinyInteger',
            'binary' => 'binary',
            'boolean' => 'boolean',
            'char' => 'char',
            'varchar' => 'string',
            'string' => 'string',
            'text' => 'text',
            'longtext' => 'longText',
            'mediumtext' => 'mediumText',
            'json' => 'json',
            'jsonb' => 'jsonb',
            'float' => 'float',
            'double' => 'double',
            'decimal' => 'decimal',
            'enum' => 'enum',
            'timestamp' => 'timestamp',
            'date' => 'date',
            'datetime' => 'dateTime',
            'time' => 'time',
            'uuid' => 'uuid',
        ];

        return $mapping[$type] ?? throw new \Exception("Unsupported column type: $type");
    }

    protected static function applyModifiers($columnDefinition, $options)
    {
        if (isset($options['notnull']) && !$options['notnull']) {
            $columnDefinition->nullable();
        } else {
            $columnDefinition->nullable(false);
        }

        if (isset($options['default'])) {
            $columnDefinition->default($options['default']);
        }

        if (isset($options['comment'])) {
            $columnDefinition->comment($options['comment']);
        }

        if (isset($options['first']) && $options['first']) {
            $columnDefinition->first();
        }

        return $columnDefinition;
    }

    private static function normalizeColumnName($name)
    {
        if (is_object($name) && method_exists($name, 'getName')) {
            return $name->getName();
        }

        if (is_array($name)) {
            return $name['name'] ?? throw new \Exception('Column name array does not have a name key');
        }

        return (string)$name;
    }

    private static function normalizeColumnType($type)
    {
        if (is_array($type)) {
            return $type['name'] ?? throw new \Exception('Column type array does not have a name key');
        }

        return (string)$type;
    }
}
