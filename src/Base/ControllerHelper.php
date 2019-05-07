<?php
namespace DNMVCS\Base;

use DNMVCS\Core\Base\ControllerHelper as Helper;

use DNMVCS\Core\App;
use DNMVCS\Basic\SuperGlobal;

class ControllerHelper extends Helper
{
    ///////
    public static function Import($file)
    {
        return App::G()->_Import($file);
    }
    public static function RecordsetUrl(&$data, $cols_map=[])
    {
        return App::G()->_RecordsetUrl($data, $cols_map);
    }
    
    public static function RecordsetH(&$data, $cols=[])
    {
        return App::G()->_RecordsetH($data, $cols);
    }
    ///////
    public static function SG()
    {
        return SuperGlobal::G();
    }
    public static function &GLOBALS($k, $v=null)
    {
        return SuperGlobal::G()->_GLOBALS($k, $v);
    }
    
    public static function &STATICS($k, $v=null)
    {
        return SuperGlobal::G()->_STATICS($k, $v, 1);
    }
    public static function &CLASS_STATICS($class_name, $var_name)
    {
        return SuperGlobal::G()->_CLASS_STATICS($class_name, $var_name);
    }
    ////
    public static function session_start(array $options=[])
    {
        return SuperGlobal::G()->session_start($options);
    }
    public function session_id($session_id=null)
    {
        return SuperGlobal::G()->session_id($session_id);
    }
    public static function session_destroy()
    {
        return SuperGlobal::G()->session_destroy();
    }
    public static function session_set_save_handler(\SessionHandlerInterface $handler)
    {
        return SuperGlobal::G()->session_set_save_handler($handler);
    }
}