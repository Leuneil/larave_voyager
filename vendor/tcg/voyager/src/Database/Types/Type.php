<?php

namespace TCG\Voyager\Database\Types;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

abstract class Type
{
    protected static $typeCategories = [];

    public const NAME = 'UNDEFINED_TYPE_NAME';
    public const NOT_SUPPORTED = 'notSupported';
    public const NOT_SUPPORT_INDEX = 'notSupportIndex';

    public static function init()
    {
        static::registerCustomTypes();
    }

    public function getName()
    {
        return static::NAME;
    }

    public static function toArray(Type $type)
    {
        $customTypeOptions = $type->customOptions ?? [];

        return array_merge([
            'name' => $type->getName(),
        ], $customTypeOptions);
    }

    public static function getTypeCategories()
    {
        if (static::$typeCategories) {
            return static::$typeCategories;
        }

        $numbers = [
            'boolean', 'tinyint', 'smallint', 'mediumint', 'integer', 'int', 'bigint', 
            'decimal', 'numeric', 'money', 'float', 'real', 'double', 'double precision'
        ];

        $strings = [
            'char', 'character', 'varchar', 'character varying', 'string', 'guid', 
            'uuid', 'tinytext', 'text', 'mediumtext', 'longtext', 'tsquery', 
            'tsvector', 'xml'
        ];

        $datetime = [
            'date', 'datetime', 'year', 'time', 'timetz', 'timestamp', 'timestamptz', 
            'datetimetz', 'dateinterval', 'interval'
        ];

        $lists = [
            'enum', 'set', 'simple_array', 'array', 'json', 'jsonb', 'json_array'
        ];

        $binary = [
            'bit', 'bit varying', 'binary', 'varbinary', 'tinyblob', 'blob', 
            'mediumblob', 'longblob', 'bytea'
        ];

        $network = [
            'cidr', 'inet', 'macaddr'
        ];

        $geometry = [
            'geometry', 'point', 'linestring', 'polygon', 'multipoint', 'multilinestring', 
            'multipolygon', 'geometrycollection'
        ];

        $objects = [
            'object'
        ];

        static::$typeCategories = [
            'numbers' => $numbers,
            'strings' => $strings,
            'datetime' => $datetime,
            'lists' => $lists,
            'binary' => $binary,
            'network' => $network,
            'geometry' => $geometry,
            'objects' => $objects,
        ];

        return static::$typeCategories;
    }

    public static function registerCustomTypes()
    {
        // Register custom types here if needed
    }

    public static function getType($name)
    {
        $typeCategories = static::getTypeCategories();
        foreach ($typeCategories as $category => $types) {
            if (in_array($name, $types)) {
                return $name;
            }
        }
        return null;
    }

    public static function hasType($name)
    {
        return in_array($name, array_merge(...array_values(static::getTypeCategories())));
    }
}
