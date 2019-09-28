<?php
namespace DNMVCS\Core;

use DNMVCS\Core\SingletonEx;

class HttpServer
{
    use SingletonEx;
    
    const DEFAULT_OPTIONS=[
            'host'=>'127.0.0.1',
            'port'=>'9527',
            'path'=>null,
            'path_document'=>'public',
        ];
    const DEFAULT_OPTIONS_EX=[
        ];
    protected $cli_options=[
            'help'=>[
                'short'=>'h',
                'desc'=>'show this help;',
            ],
            'host'=>[
                'short'=>'H',
                'desc'=>'set server host,default is 127.0.0.1',
                'required'=>true,
            ],
            'port'=>[
                'short'=>'P',
                'desc'=>'set server port,default is 8080',
                'required'=>true,
            ],
            'inner-server'=>[
                'short'=>'i',
                'desc'=>'use inner server',
            ],
            'docroot'=>[
                'short'=>'t',
                'desc'=>'document root',
                'required'=>true,
            ],
            'file'=>[
                'short'=>'f',
                'desc'=>'index file',
                'required'=>true,
            ],
            'dry'=>[
                'desc'=>'dry mode, just show cmd',
            ],
            'background'=>[
                'short'=>'b',
                'desc'=>'run background',
            ],
        ];
    protected $cli_options_ex=[];
    protected $args=[];
    protected $docroot='';
    
    protected $host;
    protected $port;
    public $options;
    public $pid=0;
    
    public static function RunQuickly($options)
    {
        return static::G()->init($options)->run();
    }
    public function init($options=[], $context=null)
    {
        $options=array_replace_recursive(static::DEFAULT_OPTIONS, static::DEFAULT_OPTIONS_EX, $options);
        
        $this->options=$options;
        
        $this->host=$options['host'];
        $this->port=$options['port'];
        $this->cli_options=array_replace_recursive($this->cli_options, $this->cli_options_ex);
        $this->args=$this->parseCaptures($this->cli_options);
        
        $this->docroot=rtrim($this->options['path'], '/').'/'.$this->options['path_document'];
        
        $this->host=$this->args['host']??$this->host;
        $this->port=$this->args['port']??$this->port;
        $this->docroot=$this->args['docroot']??$this->docroot;
        return $this;
    }
    protected function getopt($options,$longopts,&$optind)
    {
        return getopt($options,$longopts,$optind); // @codeCoverageIgnore
    }
    protected function parseCaptures($cli_options)
    {
        $shorts_map=[];
        $shorts=[];
        $longopts=[];
        
        foreach ($cli_options as $k=>$v) {
            $required=$v['required']??false;
            $optional=$v['optional']??false;
            $longopts[]=$k.($required?':':'').($optional?'::':'');
            if (isset($v['short'])) {
                $shorts[]=$v['short'].($required?':':'').($optional?'::':'');
                $shorts_map[$v['short']]=$k;
            }
        }
        $optind=null;
        $args=$this->getopt(implode('', ($shorts)), $longopts, $optind);
        $args=$args?:[];
        
        $pos_args = array_slice($_SERVER['argv'], $optind);
        
        foreach ($shorts_map as $k =>$v) {
            if (isset($args[$k]) && !isset($args[$v])) {
                $args[$v]=$args[$k];
            }
        }
        $args=array_merge($args, $pos_args);
        return $args;
    }
    public function run()
    {
        $this->showWelcome();
        
        if (isset($this->args['help'])) {
            return $this->showHelp();
        }
        return $this->runHttpServer();
    }
    public function getPid()
    {
        return $this->pid;
    }
    public function close()
    {
        if (!$this->pid) {
            return false;
        }
        posix_kill($this->pid,SIGINT );
    }
    protected function showWelcome()
    {
        echo "DNMVCS: Wellcome, for more info , use --help \n";
    }
    protected function showHelp()
    {
        $doc="Usage :\n\n";
        echo $doc;
        foreach ($this->cli_options as $k => $v) {
            $long=$k;
            
            $t=$v['short']??'';
            $t=$t?'-'.$t:'';
            if ($v['optional']??false) {
                $long.=' ['.$k.']';
                $t.=' ['.$k.']';
            }
            if ($v['required']??false) {
                $long.=' <'.$k.'>';
                $t.=' <'.$k.'>';
            }
            echo " --{$long}\t{$t}\n\t".$v['desc']."\n";
        }
        echo "Current args :\n";
        var_export($this->args);
        echo "\n";
    }
    protected function runHttpServer()
    {
        $PHP=$_SERVER['_'];
        if (realpath($PHP)===realpath($_SERVER['SCRIPT_FILENAME'])) {
            $PHP='/usr/bin/env php';
        }
        $PHP=escapeshellcmd($PHP);
        $host=escapeshellcmd($this->host);
        $port=escapeshellcmd($this->port);
        $document_root=escapeshellcmd($this->docroot);
       
        if (!isset($this->args['background'])) {
            echo "DNMVCS: RunServer by PHP inner http server $host:$port\n";
        }
        $cmd="$PHP -S $host:$port -t $document_root ";
        if (isset($this->args['dry'])) {
            echo $cmd;
            echo "\n";
            return;
        }
        if (isset($this->args['background'])) {
            $cmd.= ' > /dev/null 2>&1 & echo $!; ';
            $pid=exec($cmd);
            $this->pid=(int)$pid;
            return $pid;
        }
        return exec($cmd); // @codeCoverageIgnore
    }
}
