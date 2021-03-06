<?php
namespace tests\DuckPhp\Ext{

use DuckPhp\Ext\JsonRpcExt;
use DuckPhp\Core\HttpServer;
use TestService;
use JsonRpc\TestService as JS;
class JsonRpcExtTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        \MyCodeCoverage::G()->begin(JsonRpcExt::class);
        $path_app=\GetClassTestPath(JsonRpcExt::class);
        
        JsonRpcExt::G()->onRpcCall([
            'id'=>TestService::class,
            'method'=>'foo',
            'params'=>[
                'OK'
            ],
        ]);
        
        $options=[
            'jsonrpc_namespace'=>'JsonRpc',
            'jsonrpc_backend'=>'http://127.0.0.1:9528/json_rpc',
            'jsonrpc_is_debug'=>true,
            'jsonrpc_check_token_handler'=>function($ch){ var_dump('OOK');}
        ];
        
        JsonRpcExt::G()->init($options,null);
        
        $flag=class_exists('do_not_exoits');
        
        $server_options=[
            'path'=>$path_app,
            'path_document'=>'',
            'port'=>9528,
            'background'=>true,
            
        ];
        HttpServer::RunQuickly($server_options);
        echo HttpServer::G()->getPid();
        $data=TestService::G(JsonRpcExt::Wrap(TestService::class))->foo();
        JsonRpcExt::G()->getRealClass(TestService::G());
        JS::G()->foo();
        
        JsonRpcExt::G()->clear();
        $options['jsonrpc_backend']=['http://localdomain.dev/json_rpc','127.0.0.1:9528'];
        JsonRpcExt::G()->init($options,null);
        JS::G()->foo();

        try{
            JS::G()->the500();
        }catch(\Exception $ex){
            echo $ex;
        }
        try{
            JS::G()->throwException();
        }catch(\Exception $ex){
            echo $ex;
        }
        
               $options['jsonrpc_check_token_handler']=null;
        JsonRpcExt::G()->init($options,null);
        JS::G()->foo();
        //JS::G()->foo();
        
        var_dump($data);
        HttpServer::G()->close();
        
        \MyCodeCoverage::G()->end(JsonRpcExt::class);
        $this->assertTrue(true);
        /*
        JsonRpcExt::G()->init($options=[], $context);
        JsonRpcExt::G()->getRealClass($object);
        JsonRpcExt::G()->Wrap($class);
        JsonRpcExt::G()->_Wrap($class);
        JsonRpcExt::G()->_autoload($class);
        JsonRpcExt::G()->onRpcCall(array $input);
        JsonRpcExt::G()->curl_file_get_contents($url, $post);
        JsonRpcExt::G()->prepare_token($ch);
        JsonRpcExt::G()->__call($method, $arguments);
        //*/
    }
}
} // endnamespace  tests\DuckPhp\Ext
namespace
{

use DuckPhp\Core\SingletonEx;
class TestService
{
    use SingletonEx;
    public function foo()
    {
        return 'Client:'.DATE(DATE_ATOM);
    }
}
} //endnamespace \