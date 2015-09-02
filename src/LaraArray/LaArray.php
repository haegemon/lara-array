<?php

namespace Eloquent\LaArray;


trait LaArray
{

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        switch ($this->getCastType($key)) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return json_decode($value);
            case 'array': {
                $return = [];
                return self::arrayParse($value, $return);
            }
            case 'json':
                return json_decode($value, true);
            case 'collection':
                return new BaseCollection(json_decode($value, true));
            default:
                return $value;
        }
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
        if ($this->hasSetMutator($key)) {
            $method = 'set'.Str::studly($key).'Attribute';

            return $this->{$method}($value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        elseif (in_array($key, $this->getDates()) && $value) {
            $value = $this->fromDateTime($value);
        }

        if ($this->isArrayastable($key) && ! is_null($value)) {
            $value = self::arrayStringify($value);
        }

        if ($this->isJsonCastable($key) && ! is_null($value)) {
            $value = json_encode($value);
        }

        $this->attributes[$key] = $value;
    }

    /**
     * Determine whether a value is JSON castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isJsonCastable($key)
    {
        if ($this->hasCast($key)) {
            return in_array(
                $this->getCastType($key), ['json', 'object', 'collection'], true
            );
        }

        return false;
    }

    /**
     * Determine whether a value is Array castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isArrayastable($key)
    {
        if ($this->hasCast($key)) {
            return in_array(
                $this->getCastType($key), ['array'], true
            );
        }

        return false;
    }

    /**
     * Convert php array to pgsql array
     * @param array $array
     * @return string
     */
    static public function arrayStringify($array)
    {
        settype($set, 'array'); // can be called with a scalar or array
        $result = array();
        foreach ($set as $t) {
            if (is_array($t)) {
                $result[] = self::arrayStringify($t);
            } else {
                $t = str_replace('"', '\\"', $t); // escape double quote
                if (! is_numeric($t)) // quote only non-numeric values
                    $t = '"' . $t . '"';
                $result[] = $t;
            }
        }
        return '{' . implode(",", $result) . '}'; // format
    }

    /**
     * Convert pgsql array to php array
     * @param string $text Source string with array
     * @param array $output Returning array
     * @param bool|false $limit Limit is need
     * @param int $offset
     * @return array|int
     */
    static public function arrayParse($text, &$output, $limit = false, $offset = 1 )
    {
        if( false === $limit )
        {
            $limit = strlen( $text )-1;
            $output = [];
        }
        if( '{}' != $text )
            do
            {
                if( '{' != $text{$offset} )
                {
                    preg_match( "/(\\{?\"([^\"\\\\]|\\\\.)*\"|[^,{}]+)+([,}]+)/", $text, $match, 0, $offset );
                    $offset += strlen( $match[0] );
                    $output[] = ( '"' != $match[1]{0} ? $match[1] : stripcslashes( substr( $match[1], 1, -1 ) ) );
                    if( '},' == $match[3] ) return $offset;
                }
                else  $offset = self::arrayParse( $text, $output[], $limit, $offset+1 );
            }
            while( $limit > $offset );
        return $output;
    }
}