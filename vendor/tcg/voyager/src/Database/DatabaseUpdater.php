<?php

namespace TCG\Voyager\Database;

use Illuminate\Support\Facades\Schema;
use TCG\Voyager\Database\Schema\Table;
use TCG\Voyager\Database\Types\Type;
use Illuminate\Support\Facades\Log;

class DatabaseUpdater
{
    protected $tableArr;
    protected $table;
    protected $originalTable;

    public function __construct(array $tableArr)
    {
        Type::getTypeCategories();
        $this->table = Table::make($tableArr);
        $this->tableArr = $tableArr;
        $this->originalTable = Schema::getColumnListing($tableArr['name']);
    }

    /**
     * Update the table.
     *
     * @return void
     */
    public static function update($table)
    {
        if (!is_array($table)) {
            $table = json_decode($table, true);
        }

        if (!Schema::hasTable($table['oldName'])) {
            throw new \Exception("Table does not exist: " . $table['oldName']);
        }

        $updater = new self($table);
        $updater->updateTable();
    }

    /**
     * Updates the table.
     *
     * @return void
     */
    public function updateTable()
    {
        Table::update($this->tableArr);
    }
}
