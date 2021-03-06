<?php declare(strict_types=1);
/**
 * DuckPHP
 * From this time, you never be alone~
 */
namespace DuckPhp\Ext;

use DuckPhp\Core\SingletonEx;
use Exception;

class StrictCheck
{
    use SingletonEx;

    const MAX_TRACE_LEVEL = 20;
    
    public $options = [
            'namespace' => '',
            'namespace_controller' => '',
            'namespace_service' => '',
            'namespace_model' => '',
            'controller_base_class' => '',
            'is_debug' => true,
            'app_class' => null,
        ];
    
    protected $appClass = null;
    
    public function __construct()
    {
    }
    public function init(array $options, object $context = null)
    {
        $this->options = array_intersect_key(array_replace_recursive($this->options, $options) ?? [], $this->options);
        if ($context) {
            $this->initContext($options, $context);
        }
        if (!$context) {
            $this->appClass = $this->options['app_class'];
        }
        return $this;
    }
    protected function initContext($options = [], $context = null)
    {
        $this->appClass = get_class($context);
        $this->options['is_debug'] = $context->options['is_debug'];
        
        try {
            $context::setBeforeGetDBHandler([static::class, 'CheckStrictDB']);
        } catch (\BadMethodCallException $ex) { // @codeCoverageIgnore
            //do nothing;
        }
    }
    
    public static function CheckStrictDB()
    {
        return static::G()->checkStrictComponent('DB', 7);
    }
    ///////////////////////////////////////////////////////////
    public function getCallerByLevel($level, $parent_classes_to_skip = [])
    {
        $level += 1;
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, static::MAX_TRACE_LEVEL);
        $caller_class = $backtrace[$level]['class'] ?? '';
        // @codeCoverageIgnoreStart
        foreach ($parent_classes_to_skip as $parent_class_to_skip) {
            if (is_subclass_of($caller_class, $parent_class_to_skip) || $parent_class_to_skip === $caller_class) {
                $caller_class = $backtrace[$level + 1]['class'] ?? '';
                return $caller_class;
            }
        }
        // @codeCoverageIgnoreEnd
        return $caller_class;
    }
    public function checkEnv(): bool
    {
        if (!$this->options['is_debug']) {
            return false;
        }
        if (!$this->appClass) {
            return $this->options['is_debug'];
        }
        $flag = ($this->appClass)::G()->options['is_debug'] ?? false;
        return $flag?true:false;
    }
    public function checkStrictComponent($component_name, $trace_level, $parent_classes_to_skip = [])
    {
        if (!$this->checkEnv()) {
            return;
        }
        $caller_class = $this->getCallerByLevel($trace_level, $parent_classes_to_skip);
        
        $namespace_service = $this->options['namespace_service'];
        $namespace_controller = $this->options['namespace_controller'];
        $controller_base_class = $this->options['controller_base_class'];
        
        if (substr($caller_class, 0, strlen($namespace_controller)) == $namespace_controller) {
            throw new Exception("$component_name Can not Call By Controller");
        }
        if ($controller_base_class && (is_subclass_of($caller_class, $controller_base_class) || $caller_class === $controller_base_class)) {
            throw new Exception("$component_name Can not Call By Controller");
        }
        if (substr($caller_class, 0, strlen($namespace_service)) === $namespace_service) {
            throw new Exception("$component_name Can not Call By Service");
        }
    }
    public function checkStrictModel($trace_level)
    {
        if (!$this->checkEnv()) {
            return;
        }
        $caller_class = $this->getCallerByLevel($trace_level);

        $namespace_service = $this->options['namespace_service'];
        $namespace_model = $this->options['namespace_model'];
        
        if (substr($caller_class, 0, strlen($namespace_service)) === $namespace_service) {
            return;
        }
        if (substr($caller_class, 0, strlen($namespace_model)) === $namespace_model &&
            substr($caller_class, -strlen("ExModel")) == "ExModel") {
            return;
        }
        throw new Exception("Model Can Only call by Service or ExModel!Caller is {$caller_class}");
    }
    public function checkStrictService($service_class, $trace_level)
    {
        if (!$this->checkEnv()) {
            return;
        }
        $caller_class = $this->getCallerByLevel($trace_level);
        $namespace_model = $this->options['namespace_model'];
        $namespace_service = $this->options['namespace_service'];
        if (empty($namespace_service)) {
            return;
        }
        if (substr($caller_class, -strlen("BatchService")) === "BatchService") {
            return;
        }
        if (substr($service_class, -strlen("LibService")) === "LibService") {
            return;
        }
        if (substr($caller_class, 0, strlen($namespace_service)) === $namespace_service) {
            throw new Exception("Service($service_class) Can not call Service($caller_class)");
        }
        if (substr($caller_class, 0, strlen($namespace_model)) === $namespace_model) {
            throw new Exception("Service Can not call by Model, ($caller_class)");
        }
    }
}
