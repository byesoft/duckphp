<?php
namespace DNMVCS\Core;

use DNMVCS\Core\SingletonEx;

class Route
{
    use SingletonEx;
    
    public $options=[
            'namespace'=>'MY',
            'namespace_controller'=>'Controller',
            
            'controller_base_class'=>null,
            'controller_welcome_class'=>'Main',
            
            'controller_hide_boot_class'=>false,
            'controller_methtod_for_miss'=>'_missing',
            'controller_prefix_post'=>'do_',
            'controller_postfix'=>'',
        ];
    
    public $parameters=[];
    public $urlHandler=null;
    
    public $namespace_controller='';
    protected $controller_welcome_class='Main';
    protected $controller_index_method='index';
    protected $controller_base_class=null;
    
    protected $controller_hide_boot_class=false;
    protected $controller_methtod_for_miss=null;   
    protected $controller_prefix_post='do_';
    
    public $path_info='';
    public $request_method='';
    
    public $script_filename='';
    public $document_root='';

    public $error='';
    public $calling_path='';
    public $calling_class='';
    public $calling_method='';
    
    protected $has_bind_server_data=false;
    protected $prependedCallbackList=[];
    protected $appendedCallbackList=[];
    protected $enable_default_callback=true;
    
    public static function RunQuickly(array $options=[], callable $after_init=null)
    {
        $instance=static::G()->init($options);
        if ($after_init) {
            ($after_init)();
        }
        return $instance->run();
    }
    public static function URL($url=null)
    {
        return static::G()->_URL($url);
    }
    public static function Parameters()
    {
        return static::G()->_Parameters();
    }
    ////
    public function _URL($url=null)
    {
        if ($this->urlHandler) {
            return ($this->urlHandler)($url);
        }
        return $this->defaultURLHandler($url);
    }
    public function _Parameters()
    {
        return $this->parameters;
    }
    public function defaultURLHandler($url=null)
    {
        if (strlen($url)>0 && substr($url, 0, 1)==='/') {
            return $url;
        }
        $document_root=rtrim($this->document_root, '/');
        $basepath=substr(rtrim($this->script_filename, '/'), strlen($document_root));
        $basepath=rtrim($basepath, '/');
        
        $path_info=$this->path_info?:'/'.$this->path_info;
        
        if ($basepath=='/index.php') {
            $basepath='/';
        }
        if (''===$url) {
            return $basepath;
        }
        if ('?'==$url{0}) {
            return $basepath.$path_info.$url;
        }
        if ('#'==$url{0}) {
            return $basepath.$path_info.$url;
        }
        
        return $basepath.'/'.$url;
    }

    
    public function init($options=[], $context=null)
    {
        $options=array_intersect_key(array_replace_recursive($this->options, $options)??[], $this->options);
        $this->options=$options;
        $this->controller_prefix_post=$options['controller_prefix_post'];
        $this->enable_post_prefix=$this->controller_prefix_post?true:false;
        
        $this->controller_hide_boot_class=$options['controller_hide_boot_class'];
        $this->controller_methtod_for_miss=$options['controller_methtod_for_miss'];
        
        $this->controller_welcome_class=$options['controller_welcome_class'];
        
        
        $namespace=$options['namespace'];
        $namespace_controller=$options['namespace_controller'];
        if (substr($namespace_controller, 0, 1)!=='\\') {
            $namespace_controller=rtrim($namespace, '\\').'\\'.$namespace_controller;
        }
        $namespace_controller=trim($namespace_controller, '\\');
        $this->namespace_controller=$namespace_controller;
        
        $this->controller_base_class=$options['controller_base_class'];
        if ($this->controller_base_class && substr($this->controller_base_class, 0, 1)!=='\\') {
            $this->controller_base_class=rtrim($namespace, '\\').'\\'.$this->controller_base_class;
        }
        
        return $this;
    }
    public function setURLHandler($callback)
    {
        $this->urlHandler=$callback;
    }
    public function getURLHandler()
    {
        return $this->urlHandler;
    }
    
    public function bindServerData($server)
    {
        $this->script_filename=$server['SCRIPT_FILENAME']??'';
        $this->document_root=$server['DOCUMENT_ROOT']??'';
        $this->request_method=$server['REQUEST_METHOD']??'GET';
        if (isset($server['PATH_INFO'])) {
            $this->path_info=$server['PATH_INFO'];
        } elseif (PHP_SAPI==='cli') {
            $argv=$server['argv']??[];
            if (count($argv)>=2) {
                $this->path_info=$argv[1];
                array_shift($argv);
                array_shift($argv);
                $this->parameters=$argv;
            }
        } else {
            $this->path_info=''; // @codeCoverageIgnore
        }
        
        $this->has_bind_server_data=true;
        return $this;
    }
    public function bind($path_info, $request_method='GET')
    {
        $path_info=parse_url($path_info,PHP_URL_PATH);
        
        if (!$this->has_bind_server_data) {
            $this->bindServerData($_SERVER);
        }
        $this->path_info=$path_info;
        $this->path_info=ltrim($this->path_info??'', '/');
        
        if (isset($request_method)) {
            $this->request_method=$request_method;
        }
        return $this;
    }
    protected function beforeRun()
    {
        if (!$this->has_bind_server_data) {
            $this->bindServerData($_SERVER);
        }
        $this->path_info=ltrim($this->path_info, '/'); // TODO, kill this
    }
    public function run()
    {
        $this->beforeRun();
        
        foreach ($this->prependedCallbackList as $callback) {
            $flag=($callback)();
            if ($flag) {
                return true;
            }
        }
        
        if ($this->enable_default_callback) {
            $flag=$this->defaultRunRouteCallback($this->path_info);
            if ($flag) {
                return true;
            }
        }else{
            $this->enable_default_callback=true;
        }
        
        foreach ($this->appendedCallbackList as $callback) {
            $flag=($callback)();
            if ($flag) {
                return true;
            }
        }
        return false;
    }
    public function addRouteHook($callback, $append=true, $outter=true, $once=true)
    {
        if ($append) {
            if ($once) {
                if (in_array($callback, $this->appendedCallbackList)) {
                    return false;
                }
            }
            if ($outter) {
                array_unshift($this->appendedCallbackList, $callback);
            } else {
                array_push($this->appendedCallbackList, $callback);
            }
        } else {
            if ($once) {
                if (in_array($callback, $this->prependedCallbackList)) {
                    return false;
                }
            }
            if ($outter) {
                array_push($this->prependedCallbackList, $callback);
            } else {
                array_unshift($this->prependedCallbackList, $callback);
            }
        }
        return true;
    }
    public function add404Handler($callback)
    {
        return $this->addRouteHook($callback, true, true, false);
    }
    public function defaulToggleRouteCallback($enable=true)
    {
        $this->enable_default_callback=$enable;
    }
    public function defaultRunRouteCallback($path_info=null)
    {
        $callback=$this->defaultGetRouteCallback($path_info);
        if (null===$callback) {
            return false;
        }
        ($callback)();
        return true;
    }
    public function defaultGetRouteCallback($path_info)
    {
        $t=explode('/', $path_info);
        $method=array_pop($t);
        $path_class=implode('/', $t);
        
        $this->calling_path=$path_class?$path_info:$this->controller_welcome_class.'/'.$method;
        $this->error='';
        
        if ($this->controller_hide_boot_class && $path_class===$this->controller_welcome_class) {
            $this->error="controller_hide_boot_class! {$this->controller_welcome_class} ";
            return null;
        }
        
        $path_class=$path_class?:$this->controller_welcome_class;
        $full_class=$this->namespace_controller.'\\'.str_replace('/', '\\', $path_class).$this->options['controller_postfix'];
        if (!class_exists($full_class)) {
            $this->error="can't find class($full_class) by $path_class ";
            return null;
        }

        $this->calling_class=$full_class;
        $this->calling_method=$method;
        
        if ($this->controller_base_class && !is_subclass_of($full_class, $this->controller_base_class)) {
            $this->error="no the controller_base_class! {$this->controller_base_class} ";
            return null;
        }
        $object=$this->createControllerObject($full_class);
        return $this->getMethodToCall($object, $method);
    }
    protected function createControllerObject($full_class)
    {
        // OK, you may use other mode.
        return new $full_class();
    }
    protected function getMethodToCall($object, $method)
    {
        $method=$method===''?$this->controller_index_method:$method;
        if (substr($method, 0, 2)=='__') {
            $this->error='can not call hidden method';
            return null;
        }
        if ($this->controller_prefix_post && $this->request_method==='POST' &&  method_exists($object, $this->controller_prefix_post.$method)) {
            $method=$this->controller_prefix_post.$method;
        }
        if ($this->controller_methtod_for_miss) {
            if ($method===$this->controller_methtod_for_miss) {
                $this->error='can not direct call controller_methtod_for_miss ';
                return null;
            }
            if (!method_exists($object, $method)) {
                $method=$this->controller_methtod_for_miss;
            }
        }
        if (!is_callable([$object,$method])) {
            $this->error='method can not call';
            return null;
        }
        return [$object,$method];
    }
    
    ////
    public function getRouteError()
    {
        return $this->error;
    }
    public function getRouteCallingPath()
    {
        return $this->calling_path;
    }
    public function getRouteCallingClass()
    {
        return $this->calling_class;
    }
    public function getRouteCallingMethod()
    {
        return $this->calling_method;
    }
    public function setRouteCallingMethod($calling_method)
    {
        $this->calling_method=$calling_method;
    }
}
