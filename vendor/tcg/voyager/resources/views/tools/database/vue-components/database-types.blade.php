@section('database-types-template')

<div>
    <select :value="selectedType" @change="onTypeChange" class="form-control">
        <optgroup v-for="(types, category) in dbTypes" :label="category">
            <option v-for="type in types" :value="type">
                @{{ type.toUpperCase() }}
            </option>
        </optgroup>
    </select>
    <div v-if="selectedTypeNotSupported">
        <small>{{ __('voyager::database.type_not_supported') }}</small>
    </div>
</div>

@endsection

<script>
    let databaseTypes = {!! json_encode($db->types) !!};

    function getDbType(name) {
        let type;
        name = name.toLowerCase().trim();

        for (category in databaseTypes) {
            type = databaseTypes[category].find(function (type) {
                // Remove length or unsigned attributes from comparison
                let normalizedType = type.toLowerCase().replace(/\(\d+\)/, '').replace(/ unsigned/, '');
                return name == normalizedType;
            });

            if (type) {
                return type.split('(')[0].trim(); // Return only the type name without length or other attributes
            }
        }

        toastr.error("{{ __('voyager::database.unknown_type') }}: " + name);

        // fallback to a default type
        return databaseTypes.numbers[0].split('(')[0].trim(); // Return only the type name without length or other attributes
    }

    Vue.component('database-types', {
        props: {
            column: {
                type: Object,
                required: true
            }
        },
        data() {
            return {
                dbTypes: databaseTypes,
                selectedType: this.column.type ? this.column.type.toLowerCase() : '', // Bind selected type to the column type
                selectedTypeNotSupported: this.column.type ? getDbType(this.column.type).notSupported : false
            };
        },
        template: `@yield('database-types-template')`,
        methods: {
            onTypeChange(event) {
                this.selectedType = event.target.value.toLowerCase();
                this.selectedTypeNotSupported = getDbType(event.target.value).notSupported;
                this.$emit('typeChanged', this.getType(event.target.value));
            },
            getType(name) {
                return getDbType(name);
            }
        }
    });
</script>