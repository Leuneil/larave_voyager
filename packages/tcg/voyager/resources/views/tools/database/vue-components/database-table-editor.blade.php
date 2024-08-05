@section('database-table-editor-template')

<div class="panel panel-bordered">
    <div class="panel-body">
        <div class="row">
        @if($db->action == 'update')
            <div class="col-md-12">
        @else
            <div class="col-md-6">
        @endif
                <label for="name">{{ __('voyager::database.table_name') }}</label><br>
                <input v-model.trim="table.name" type="text" class="form-control" placeholder="{{ __('voyager::database.table_name') }}" required pattern="{{ $db->identifierRegex }}">
            </div>

        @if($db->action == 'create')
            <div class="col-md-3 col-sm-4 col-xs-6">
                <label for="create_model">{{ __('voyager::database.create_model_table') }}1</label><br>
                <input type="checkbox" name="create_model" data-toggle="toggle"
                       data-on="{{ __('voyager::generic.yes_please') }}" data-off="{{ __('voyager::generic.no_thanks') }}">
            </div>
            {{--
                Hide migration button until feature is available.
                 <div class="col-md-3 col-sm-4 col-xs-6">
                    <label for="create_migration">{{ __('voyager::database.create_migration') }}</label><br>
                    <input disabled type="checkbox" name="create_migration" data-toggle="toggle"
                           data-on="{{ __('voyager::generic.yes_please') }}" data-off="{{ __('voyager::generic.no_thanks') }}">
                </div>
            --}}
        @endif
        </div><!-- .panel-body .row -->

        <div v-if="compositeIndexes.length" v-once class="alert alert-danger">
            <p>{{ __('voyager::database.no_composites_warning') }}</p>
        </div>

        <div id="alertsContainer"></div>

        <template v-if="tableHasColumns">
            <p>{{ __('voyager::database.table_columns') }}</p>

            <table class="table table-bordered" style="width:100%;">
                <thead>
                <tr>
                    <th>{{ __('voyager::generic.name') }}</th>
                    <th>{{ __('voyager::generic.type') }}</th>
                    <th>{{ __('voyager::generic.length') }}</th>
                    <th>{{ __('voyager::generic.not_null') }}</th>
                    <th>{{ __('voyager::generic.unsigned') }}</th>
                    <th>{{ __('voyager::generic.auto_increment') }}</th>
                    <th>{{ __('voyager::generic.index') }}</th>
                    <th>{{ __('voyager::generic.default') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                    <database-column
                        v-for="(column, index) in table.columns"
                        :column="column"
                        :index="getColumnsIndex(column.name)"
                        :key="index"
                        @columnNameUpdated="renameColumn"
                        @columnDeleted="deleteColumn"
                        @indexAdded="addIndex"
                        @indexDeleted="deleteIndex"
                        @indexUpdated="updateIndex"
                        @indexChanged="onIndexChange"
                    ></database-column>
                </tbody>
            </table>
        </template>
        <div v-else>
          <p>{{ __('voyager::database.table_no_columns') }}</p>
        </div>

        <div style="text-align:center">
            <database-table-helper-buttons
                @columnAdded="addColumn"
            ></database-table-helper-buttons>
        </div>
    </div><!-- .panel-body -->

    <div class="panel-footer">
        <input type="submit" class="btn btn-primary pull-right"
               value="@if($db->action == 'update'){{ __('voyager::database.update_table') }}@else{{ __('voyager::database.create_new_table') }}@endif"
               :disabled="!tableHasColumns">
        <div style="clear:both"></div>
    </div>
</div><!-- .panel -->


@endsection

@include('voyager::tools.database.vue-components.database-column')
@include('voyager::tools.database.vue-components.database-table-helper-buttons')


<script>
    Vue.component('database-table-editor', {
        props: {
            table: {
                type: Object,
                required: true
            }
        },
        data() {
            return {
                emptyIndex: {
                    type: '',
                    name: ''
                },
                compositeIndexes: []
            };
        },
        template: `@yield('database-table-editor-template')`,
        mounted() {
            // Initialize table.columns as an empty array if it's undefined
            if (!Array.isArray(this.table.columns)) {
                this.$set(this.table, 'columns', []);
            }

            // Ensure the "id" column is created by default with the required properties
            if (!this.tableHasColumns) {
                this.addDefaultIdColumn();
            }

            // Add warning to columns that are part of a composite index
            this.compositeIndexes = this.getCompositeIndexes();
            let compositeColumns = this.getIndexesColumns(this.compositeIndexes);

            for (let col in compositeColumns) {
                let column = this.getColumn(compositeColumns[col]);
                if (column) {
                    column.composite = true;
                }
            }

            // Display errors
            @if(Session::has('alerts'))
                helpers.displayAlerts(alerts, helpers.bootstrapAlerter({dismissible: true}), 'error');
            @endif
        },
        computed: {
            tableHasColumns() {
                // Ensure table and columns are defined
                if (!this.table || !this.table.columns) {
                    return false;
                }

                return this.table.columns.length > 0;
            }
        },
        methods: {
            addDefaultIdColumn() {
                this.addColumn({
                    name: 'id',
                    type: 'bigint',
                    notnull: true,
                    unsigned: true,
                    autoincrement: true,
                    index: 'PRIMARY'
                });
                this.addPrimaryKey('id');  // Add primary key index
            },
            addColumn(column) {
                // Ensure table.columns is an array
                if (!Array.isArray(this.table.columns)) {
                    this.$set(this.table, 'columns', []);
                }

                column.name = column.name.trim();

                if (column.name && this.hasColumn(column.name)) {
                    return toastr.error("{{ __('voyager::database.column') }} " + column.name + " {{ __('voyager::database.already_exists') }}");
                }

                this.table.columns.push(
                    JSON.parse(JSON.stringify(column))
                );

                // Add primary key index if specified
                if (column.index === 'PRIMARY') {
                    this.addPrimaryKey(column.name);
                }
            },
            addPrimaryKey(columnName) {
                // Ensure indexes is an array
                if (!Array.isArray(this.table.indexes)) {
                    this.$set(this.table, 'indexes', []);
                }

                // Remove existing primary key
                this.table.indexes = this.table.indexes.filter(index => index.type !== 'PRIMARY');

                // Add primary key index
                this.table.indexes.push({
                    columns: [columnName],
                    type: 'PRIMARY',
                    name: 'primary'
                });
                this.table.primaryKeyName = 'primary';
            },
            getColumn(name) {
                if (!Array.isArray(this.table.columns)) {
                    return null;
                }

                name = name.toLowerCase().trim();

                return this.table.columns.find(function (column) {
                    return name == column.name.toLowerCase();
                });
            },
            hasColumn(name) {
                return !!this.getColumn(name);
            },
            renameColumn(column) {
                let newName = column.newName.trim();
                column = column.column;

                let existingColumn;
                if ((existingColumn = this.getColumn(newName)) && (existingColumn !== column)) {
                    return toastr.error("{{ __('voyager::database.column') }} " + newName + " {{ __('voyager::database.already_exists') }}");
                }

                let index = this.getColumnsIndex(column.name);
                if (index !== this.emptyIndex) {
                    index.columns = [newName];
                }

                column.name = newName;
            },
            deleteColumn(column) {
                var columnPos = this.table.columns.indexOf(column);

                if (columnPos !== -1) {
                    this.table.columns.splice(columnPos, 1);

                    // Delete associated index
                    this.deleteIndex(this.getColumnsIndex(column.name));
                }
            },
            getColumnsIndex(columns) {
                if (!Array.isArray(columns)) {
                    columns = [columns];
                }

                let index = null;

                for (let i in this.table.indexes) {
                    if (!($(this.table.indexes[i].columns).not(columns).get().length)) {
                        index = this.table.indexes[i];
                        break;
                    }
                }

                if (!index) {
                    index = this.emptyIndex;
                }

                index.table = this.table.name;
                return index;
            },
            onIndexChange(index) {
                if (index.old === this.emptyIndex) {
                    return this.addIndex({
                        columns: index.columns,
                        type: index.newType
                    });
                }

                if (index.newType == '') {
                    return this.deleteIndex(index.old);
                }

                return this.updateIndex(index.old, index.newType);
            },
            addIndex(index) {
                if (!Array.isArray(this.table.indexes)) {
                    this.table.indexes = [];
                }

                if (index.type == 'PRIMARY') {
                    if (this.table.primaryKeyName) {
                        return toastr.error("{{ __('voyager::database.table_has_index') }}");
                    }

                    this.addPrimaryKey(index.columns[0]);
                } else {
                    this.setIndexName(index);
                    this.table.indexes.push(index);
                }

            },
            deleteIndex(index) {
                var indexPos = this.table.indexes.indexOf(index);

                if (indexPos !== -1) {
                    if (index.type == 'PRIMARY') {
                        this.table.primaryKeyName = false;
                    }

                    this.table.indexes.splice(indexPos, 1);
                }

            },
            updateIndex(index, newType) {
                // Handle removing old primary key first
                if (index.type === 'PRIMARY' && newType !== 'PRIMARY') {
                    this.deleteIndex(index);
                }

                // Add new index type if necessary
                if (newType === 'PRIMARY') {
                    if (this.table.primaryKeyName) {
                        return toastr.error("{{ __('voyager::database.table_has_index') }}");
                    }
                    this.addPrimaryKey(index.columns[0]);
                } else {
                    index.type = newType;
                    this.setIndexName(index);
                }

            },
            setIndexName(index) {
                if (index.type == 'PRIMARY') {
                    index.name = 'primary';
                } else {
                    index.name = '';
                }
            },
            getCompositeIndexes() {
                let composite = [];

                for (let i in this.table.indexes) {
                    if (this.table.indexes[i].isComposite) {
                        composite.push(this.table.indexes[i]);
                    }
                }

                return composite;
            },
            getIndexesColumns(indexes) {
                let columns = [];

                for (let i in indexes) {
                    for (let col in indexes[i].columns) {
                        columns.push(indexes[i].columns[col]);
                    }
                }

                return [...new Set(columns)];
            }
        }
    });
</script>
