<?php declare(strict_types=1);
/**
 * DuckPHP
 * From this time, you never be alone~
 */
namespace DuckPhp\Ext;

use DuckPhp\Core\SingletonEx;

class FacadesBase
{
    use SingletonEx;
    
    public function __construct()
    {
    }
    public static function __callStatic($name, $arguments)
    {
        $callback = FacadesAutoLoader::G()->getFacadesCallback(static::class, $name);
        if (!$callback) {
            throw new \Exception("BadCall");
        }
        $ret = call_user_func_array($callback, $arguments);
        return $ret;
    }
}
