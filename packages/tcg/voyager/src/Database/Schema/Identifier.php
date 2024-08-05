<?php

namespace TCG\Voyager\Database\Schema;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

abstract class Identifier
{
    // Warning: Do not modify this
    public const REGEX = '^[a-zA-Z_][a-zA-Z0-9_]*$';

    public static function validate($identifier, $asset = '')
    {
        // Ensure the identifier is a string
        
        if (!is_string($identifier)) {
            throw new \Exception("{$asset} Identifier must be a string, " . gettype($identifier) . " given.");
        }

        $identifier = trim($identifier);

        

        $validator = Validator::make(['identifier' => $identifier], [
            'identifier' => 'required|regex:/'.static::REGEX.'/',
        ]);

        // dd($validator);

        if ($validator->fails()) {
            throw new \Exception("{$asset} Identifier '{$identifier}' is invalid");
        }

        return $identifier;
    }
}
