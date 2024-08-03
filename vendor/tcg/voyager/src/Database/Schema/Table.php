<?php

namespace TCG\Voyager\Database\Schema;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class Table
{
    public $name;
    public $options;
    public $columns;

    public function __construct($name, $options = [], $columns = [])
    {
        $this->name = $name;
        $this->options = $options;
        $this->columns = $columns;
    }

    public static function make(array $table)
    {
        if (!isset($table['name'])) {
            throw new \Exception("Table name is required.");
        }

        $name = trim($table['name']);
        $oldName = $table['oldName'] ?? null;
        $name = Identifier::validate($name, 'Table');

        $options = $table['options'] ?? [];
        $columns = $table['columns'] ?? [];

        if (!Schema::hasTable($oldName)) {
            Schema::create($name, function ($tableBuilder) use ($columns) {
                foreach ($columns as $column) {
                    Column::make($column, $tableBuilder, true); // Creating new columns
                }
            });
        } else {
            if ($oldName && $oldName !== $name) {
                Schema::rename($oldName, $name);
            }
            self::update($table);
        }

        return new self($name, $options, $columns);
    }
    

    public static function update(array $table)
    {
        $name = trim($table['name']);
        $columns = $table['columns'] ?? [];
        $newColumns = []; // To track new columns that shouldn't be modified

        $isDirty = false;
    
        try {
            Schema::table($name, function (Blueprint $tableBuilder) use ($columns, $name, &$isDirty, &$newColumns) {
                // First, create any columns that do not exist
                foreach ($columns as $index => $column) {
                    if (!Schema::hasColumn($name, $column['name'])) {
                        // Get the name of the preceding column
                        $precedingColumn = $index > 0 ? $columns[$index - 1]['name'] : null;
    
                        // Pass the 'after' column name if it exists
                        Column::make($column, $tableBuilder, true, $precedingColumn); 
                        $newColumns[] = $column['name']; // Track this as a new column
                        $isDirty = true;
                    }
                }
    
                // Then modify existing columns (if necessary)
                foreach ($columns as $column) {
                    if (in_array($column['name'], $newColumns)) {
                        continue; // Skip new columns that were just created
                    }
    
                    if (Schema::hasColumn($name, $column['name'])) {
                        // Modify the existing column
                        $isDirty = true;
                        $method = self::getColumnTypeMethod($column['type']);
                        if ($method) {
                            $columnBuilder = $tableBuilder->{$method}($column['name'], $column['length'] ?? null, $column['unsigned']);
                            if (in_array($column['type'], ['bigint', 'integer', 'tinyint', 'smallint', 'mediumint', 'float', 'double', 'decimal'])) {
                                if ($column['unsigned']) {
                                    $columnBuilder->unsigned();
                                }
                            }
                            if (isset($column['notnull']) && !$column['notnull']) {
                                $columnBuilder->nullable();
                            } else {
                                $columnBuilder->nullable(false);
                            }
    
                            if ($column['autoincrement']) {
                                $columnBuilder->autoIncrement();
                            }
                            
                            if (isset($column['default'])) {
                                $columnBuilder->default($column['default']);
                            }
                            
                            $columnBuilder->change();
                        } 
                    }
                }
    
                // Finally, drop any columns that are not in the provided array
                $existingColumns = Schema::getColumnListing($name);
                foreach ($existingColumns as $existingColumn) {
                    if (!in_array($existingColumn, array_column($columns, 'name'))) {
                        $tableBuilder->dropColumn($existingColumn);
                        $isDirty = true;
                    }
                }
            });
    
        } catch (\Exception $e) {
            throw $e; // Re-throw the exception after logging it
        }

    }

    protected static function getColumnTypeMethod($type)
    {
        $type = strtolower($type);
        $mapping = [
            'bigint' => 'bigInteger',
            'binary' => 'binary',
            'boolean' => 'boolean',
            'char' => 'char',
            'date' => 'date',
            'datetime' => 'dateTime',
            'decimal' => 'decimal',
            'double' => 'double',
            'enum' => 'enum',
            'float' => 'float',
            'integer' => 'integer',
            'json' => 'json',
            'jsonb' => 'jsonb',
            'longtext' => 'longText',
            'mediumint' => 'mediumInteger',
            'mediumtext' => 'mediumText',
            'smallint' => 'smallInteger',
            'string' => 'string',
            'varchar' => 'string',
            'text' => 'text',
            'time' => 'time',
            'timestamp' => 'timestamp',
            'tinyint' => 'tinyInteger',
            'uuid' => 'uuid',
        ];

        return $mapping[$type] ?? null;
    }

    protected static function updateIndex($tableName, $index)
    {
        Schema::table($tableName, function (Blueprint $table) use ($index) {
            if ($index['type'] === 'PRIMARY') {
                $table->primary($index['columns']);
            } elseif ($index['type'] === 'UNIQUE') {
                $table->unique($index['columns']);
            } elseif ($index['type'] === 'INDEX') {
                $table->index($index['columns']);
            }
        });
    }

    public static function drop($name)
    {
        Schema::dropIfExists($name);
    }

    public static function rename($oldName, $newName)
    {
        Schema::rename($oldName, $newName);
    }

    public static function hasTable($name)
    {
        return Schema::hasTable($name);
    }
}
