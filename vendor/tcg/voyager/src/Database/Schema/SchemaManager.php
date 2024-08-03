<?php

namespace TCG\Voyager\Database\Schema;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Schema\Blueprint;


abstract class SchemaManager
{
    public static function manager()
    {
        return DB::connection()->getSchemaBuilder();
    }

    public static function tableExists($table)
    {
        if (!is_array($table)) {
            $table = [$table];
        }

        foreach ($table as $tableName) {
            if (!Schema::hasTable($tableName)) {
                return false;
            }
        }

        return true;
    }

    public static function listTables()
    {
        $tables = DB::select('SHOW TABLES');
        return array_map('current', $tables);
    }

    // New method to list table names
    public static function listTableNames()
    {
        $tables = self::listTables();
        return array_map(function ($table) {
            return DB::getTablePrefix() . $table;
        }, $tables);
    }

    /**
     * @param string $tableName
     *
     * @return \TCG\Voyager\Database\Schema\Table
     */
    public static function listTableDetails($tableName)
    {
        $columns = self::describeTable($tableName);

        $convertedColumns = self::convertColumns($columns);

        $indexes = self::listTableIndexes($tableName);
        $foreignKeys = self::listTableForeignKeys($tableName);

        return new Table($tableName, $convertedColumns, $indexes, $foreignKeys, []);
    }

    /**
     * Describes the columns in the given table.
     *
     * @param string $tableName
     *
     * @return array
     */
    public static function describeTable($tableName)
    {

        $columns = Schema::getColumnListing($tableName);

        $detailedColumns = [];

        foreach ($columns as $columnName) {

            $type = Schema::getColumnType($tableName, $columnName);
            $query = "SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'";
            $details = DB::select($query);

            if (!empty($details)) {
                $details = $details[0];

                $detailedColumns[] = [
                    'field' => $details->Field,
                    'type' => $type,
                    'key' => $details->Key,
                    'default' => $details->Default,
                    'null' => $details->Null === 'NO' ? false : true,
                    'length' => null,
                    'precision' => null,
                    'scale' => null,
                    'autoincrement' => strpos($details->Extra, 'auto_increment') !== false,
                    'comment' => $details->Comment ?? null,
                ];
            }
        }
        
        return $detailedColumns;
    }


    public static function convertColumns($columns)
    {
        $convertedColumns = [];

        foreach ($columns as $column) {
            $convertedColumns[] = [
                'field' => $column['name'],
                'type' => $column['type'],
                'default' => $column['default'],
                'notnull' => $column['null'],
                'length' => $column['length'],
                'precision' => $column['precision'],
                'scale' => $column['scale'],
                'autoincrement' => $column['autoincrement'],
                'comment' => $column['comment'],
            ];
        }

        return $convertedColumns;
    }


    public static function listTableColumnNames($tableName)
    {
        return Schema::getColumnListing($tableName);
    }

    public static function createTable($table)
    {
        // Convert stdClass to an associative array if needed
        if (is_object($table)) {
            $table = (array) $table;
            $table['columns'] = (array) $table['columns'];
            $table['indexes'] = isset($table['indexes']) ? (array) $table['indexes'] : [];
            $table['foreignKeys'] = isset($table['foreignKeys']) ? (array) $table['foreignKeys'] : [];
        }

        // Check if the table already exists
        if (Schema::hasTable($table['name'])) {
            return;
        }
        
        // Create the table if it doesn't exist
        Schema::create($table['name'], function (Blueprint $blueprint) use ($table) {
            foreach ($table['columns'] as $column) {
                $blueprint->addColumn($column['type'], $column['name'], $column['options'] ?? []);
            }
            foreach ($table['indexes'] as $index) {
                $blueprint->index($index['columns'], $index['name']);
            }
            foreach ($table['foreignKeys'] as $foreignKey) {
                $blueprint->foreign($foreignKey['columns'])
                    ->references($foreignKey['references'])
                    ->on($foreignKey['on'])
                    ->onDelete($foreignKey['onDelete']);
            }
        });
    }
    
    


    private static function listTableIndexes($tableName)
    {
        $indexes = [];
        $result = DB::select("SHOW INDEXES FROM `{$tableName}`");

        foreach ($result as $row) {
            $indexes[$row->Key_name]['name'] = $row->Key_name;
            $indexes[$row->Key_name]['columns'][] = $row->Column_name;
            $indexes[$row->Key_name]['type'] = $row->Non_unique ? 'index' : 'unique';

            if ($row->Key_name === 'PRIMARY') {
                $indexes[$row->Key_name]['type'] = 'primary';
            }
        }

        return $indexes;
    }

    private static function listTableForeignKeys($tableName)
    {
        $foreignKeys = [];
        $result = DB::select("SELECT
            k.COLUMN_NAME,
            k.REFERENCED_TABLE_NAME,
            k.REFERENCED_COLUMN_NAME,
            k.CONSTRAINT_NAME
            FROM
            information_schema.KEY_COLUMN_USAGE k
            WHERE
            k.TABLE_NAME = '{$tableName}'
            AND
            k.CONSTRAINT_SCHEMA = DATABASE()
            AND
            k.REFERENCED_TABLE_NAME IS NOT NULL");

        foreach ($result as $row) {
            $foreignKeys[$row->CONSTRAINT_NAME]['columns'][] = $row->COLUMN_NAME;
            $foreignKeys[$row->CONSTRAINT_NAME]['references'] = $row->REFERENCED_COLUMN_NAME;
            $foreignKeys[$row->CONSTRAINT_NAME]['on'] = $row->REFERENCED_TABLE_NAME;
        }

        return $foreignKeys;
    }

    public static function dropTable($tableName)
    {
        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        } else {
            throw new \Exception("Table '{$tableName}' does not exist.");
        }
    }
}
