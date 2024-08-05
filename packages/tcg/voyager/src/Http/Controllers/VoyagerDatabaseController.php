<?php

namespace TCG\Voyager\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TCG\Voyager\Database\DatabaseUpdater;
use TCG\Voyager\Database\Schema\Column;
use TCG\Voyager\Database\Schema\Identifier;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Database\Schema\Table;
use TCG\Voyager\Database\Types\Type;
use TCG\Voyager\Events\TableAdded;
use TCG\Voyager\Events\TableDeleted;
use TCG\Voyager\Events\TableUpdated;
use TCG\Voyager\Facades\Voyager;
use Illuminate\Support\Facades\Schema;

class VoyagerDatabaseController extends Controller
{
    public function index()
    {
        $this->authorize('browse_database');

        $dataTypes = Voyager::model('DataType')->select('id', 'name', 'slug')->get()->keyBy('name')->toArray();

        $tables = array_map(function ($table) use ($dataTypes) {
            $table = Str::replaceFirst(DB::getTablePrefix(), '', $table);

            return (object) [
                'prefix'     => DB::getTablePrefix(),
                'name'       => $table,
                'slug'       => $dataTypes[$table]['slug'] ?? null,
                'dataTypeId' => $dataTypes[$table]['id'] ?? null,
            ];
        }, SchemaManager::listTables());

        return Voyager::view('voyager::tools.database.index')->with(compact('dataTypes', 'tables'));
    }

    public function create()
    {
        $this->authorize('browse_database');

        $db = $this->prepareDbManager('create');

        return Voyager::view('voyager::tools.database.edit-add', compact('db'));
    }

    public function store(Request $request)
    {
        $this->authorize('browse_database');
        try {
            $conn = 'database.connections.'.config('database.default');
            Type::registerCustomTypes();
    
            $tableData = $request->table;
            if (!is_array($tableData)) {
                $tableData = json_decode($tableData, true);
            }
            $tableData['options']['collate'] = config($conn.'.collation', 'utf8mb4_unicode_ci');
            $tableData['options']['charset'] = config($conn.'.charset', 'utf8mb4');
    
            // Ensure Table::make() returns a TCG\Voyager\Database\Schema\Table instance
            $table = Table::make($tableData);
    
            SchemaManager::createTable($table);
    
            if (isset($request->create_model) && $request->create_model == 'on') {
                $modelNamespace = config('voyager.models.namespace', app()->getNamespace());
                $params = [
                    'name' => $modelNamespace.Str::studly(Str::singular($table->name)),
                ];
    
                if (isset($request->create_migration) && $request->create_migration == 'on') {
                    $params['--migration'] = true;
                }
    
                Artisan::call('voyager:make:model', $params);
            } elseif (isset($request->create_migration) && $request->create_migration == 'on') {
                Artisan::call('make:migration', [
                    'name'    => 'create_'.$table->name.'_table',
                    '--table' => $table->name,
                ]);
            }
    
            event(new TableAdded($table));
    
            return redirect()
                ->route('voyager.database.index')
                ->with($this->alertSuccess(__('voyager::database.success_create_table', ['table' => $table->name])));
        } catch (Exception $e) {
            return back()->with($this->alertException($e))->withInput();
        }
    }
            
    

    public function edit($table)
    {
        $this->authorize('browse_database');

        $db = $this->prepareDbManager('update', $table);

        return Voyager::view('voyager::tools.database.edit-add', compact('db'));
    }

    public function update(Request $request)
    {
        
        $this->authorize('browse_database');
    
        $table = json_decode($request->table, true);
        
        try {
            // Fetch existing table columns to check for updates
            $existingColumns = Schema::getColumnListing($table['oldName']);
            
            foreach ($table['columns'] as &$column) {
                if (in_array($column['name'], $existingColumns)) {
                    $column['oldName'] = $column['name']; // Set oldName for existing columns
                }
            }

            
            DatabaseUpdater::update($table);
            event(new TableUpdated($table));
        } catch (Exception $e) {
            return back()->with($this->alertException($e))->withInput();
        }
    
        return redirect()
            ->route('voyager.database.index')
            ->with($this->alertSuccess(__('voyager::database.success_create_table', ['table' => $table['name']])));
    }
    
    
    

    protected function prepareDbManager($action, $table = null)
    {
        $types = Type::getTypeCategories();
        $oldTable = old('table') ? json_decode(old('table')) : null; // Handle old table
        $tableDetails = null;
        $convertedColumns = [];
        $tableIndexes = [];
        if ($table) {
            $columns = Schema::getColumnListing($table);
    
            foreach ($columns as $column) {
                $columnType = Schema::getColumnType($table, $column);
                $default = DB::select("SHOW COLUMNS FROM `$table` WHERE Field = '$column'")[0]->Default ?? null;
    
                $columnDetailQuery = "SHOW COLUMNS FROM `$table` WHERE Field = '$column'";
                $columnDetails = DB::select($columnDetailQuery);
    
                if (!empty($columnDetails)) {
                    $notnull = $columnDetails[0]->Null === 'NO';
                    $autoincrement = strpos($columnDetails[0]->Extra, 'auto_increment') !== false;
                    $unsigned = strpos($columnDetails[0]->Type, 'unsigned') !== false;
                    $comment = null;
    
                    $indexQuery = "SHOW INDEX FROM `$table` WHERE Column_name = '$column'";
                    $indexDetails = DB::select($indexQuery);
    
                    $index = [];
                    foreach ($indexDetails as $indexDetail) {
                        if ($indexDetail->Key_name === 'PRIMARY') {
                            $index = 'PRIMARY';
                            break;
                        } elseif ($indexDetail->Non_unique == 0) {
                            $index = 'UNIQUE';
                        } elseif ($indexDetail->Non_unique == 1) {
                            $index = 'INDEX';
                        }
                    }
    
                    $type = strtoupper(explode('(', $columnDetails[0]->Type)[0]);
                    $type = str_replace(' UNSIGNED', '', $type); // Remove unsigned attribute
                    $length = null;
    
                    if (preg_match('/\((.*?)\)/', $columnDetails[0]->Type, $matches)) {
                        $length = $matches[1];
                    }
    
                    $convertedColumns[] = [
                        'name' => $column,
                        'type' => $type,
                        'length' => $length,
                        'default' => $default,
                        'notnull' => $notnull,
                        'autoincrement' => $autoincrement,
                        'unsigned' => $unsigned,
                        'comment' => $comment,
                        'index' => $index,
                    ];
    
                    $tableIndexes[] = [
                        'columns' => [$column],
                        'type' => $index,
                    ];
                }
            }
    
            $tableDetails = [
                'columns' => $convertedColumns,
                'indexes' => $tableIndexes,
                'name' => $table,
                'oldName' => $table,
            ];
        }
        
        $formAction = $action == 'update' ? route('voyager.database.update', ['database' => $table]) : route('voyager.database.store');
        
        
        return (object) [
            'types' => $types,
            'tableDetails' => (object) $tableDetails,
            'action' => $action,
            'table' => (object) $tableDetails,
            'formAction' => $formAction,
            'identifierRegex' => Identifier::REGEX,
            'oldTable' => $oldTable,
        ];
    }

    
    
    

    public function cleanOldAndCreateNew($originalName, $tableName)
    {
        if (!empty($originalName) && $originalName != $tableName) {
            $dt = DB::table('data_types')->where('name', $originalName);
            if ($dt->get()) {
                $dt->delete();
            }

            $perm = DB::table('permissions')->where('table_name', $originalName);
            if ($perm->get()) {
                $perm->delete();
            }

            $params = ['name' => Str::studly(Str::singular($tableName))];
            Artisan::call('voyager:make:model', $params);
        }
    }

    public function reorder_column(Request $request)
    {
        $this->authorize('browse_database');

        if ($request->ajax()) {
            $table = $request->table;
            $column = $request->column;
            $after = $request->after;
            if ($after == null) {
                // SET COLUMN TO THE TOP
                DB::query("ALTER $table MyTable CHANGE COLUMN $column FIRST");
            }

            return 1;
        }

        return 0;
    }

    public function show($table)
    {
        $this->authorize('browse_database');

        $additional_attributes = [];
        $model_name = Voyager::model('DataType')->where('name', $table)->pluck('model_name')->first();
        if (isset($model_name)) {
            $model = app($model_name);
            if (isset($model->additional_attributes)) {
                foreach ($model->additional_attributes as $attribute) {
                    $additional_attributes[$attribute] = [];
                }
            }
        }
        
        return response()->json(collect(SchemaManager::describeTable($table))->merge($additional_attributes));
    }

    public function destroy($table)
    {
        $this->authorize('browse_database');

        try {
            SchemaManager::dropTable($table);
            event(new TableDeleted($table));

            return redirect()
                ->route('voyager.database.index')
                ->with($this->alertSuccess(__('voyager::database.success_delete_table', ['table' => $table])));
        } catch (Exception $e) {
            return back()->with($this->alertException($e));
        }
    }
}
