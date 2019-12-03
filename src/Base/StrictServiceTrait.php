<?php declare(strict_types=1);
namespace DuckPhp\Base;

use DuckPhp\Core\SingletonEx;
use DuckPhp\Core\App;

trait StrictServiceTrait
{
    use SingletonEx {
        G as _ParentG;
    }
    public static function G($object=null)
    {
        App::G()->checkStrictService(static::class);
        return static::_ParentG($object);
    }
}
