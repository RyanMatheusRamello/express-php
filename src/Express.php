<?php

namespace ExpressPHP;

class Express {

    private $_events = [];

    /**
     * Contem as variaveis locais definidas pela função SET
     * @var object
     */
    public $local;

    /**
     * Contem a variavel de rotas da requisição
     * @var Router
     */
    public $router;

    /**
     * Contem a variavel Request da requisição
     * @var Request
     */
    private $request;

    /**
     * Contem a variavel Response da requisição
     * @var Response
     */
    private $response;

    /**
     * Contem a variavel que armazena as template engine configuradas
     * @var array
     */
    public $engines = [];

    /**
     * Contem as variaveis definidas pelo SET, mas que são usadas pela propria classe
     * @var array
     */
    public $options = [];

    /**
     * Inicia a classe
     */
    public function __construct(){

    	\ExpressPHP\Static\MimeTypes::init();
        $this->request = new Request($this);
        $this->response = new Response($this, $this->request);
        $this->router = new Router($this, $this->request, $this->response);
        $this->locals = (object) [];
        $htmlEngine = function($folder, $name, $obj, $callback){
            $handle = fopen($folder."/".$name, "r");
            $content = fread($handle, filesize($folder."/".$name));
            fclose($handle);
            $callback($content);
        };
        $this->engine("html", $htmlEngine);
        $this->set("views", "./");
        $this->set("view engine", "html");
        $this->on("error", function($req, $res, $err){
            $res->send($err->getMessage());
        });

    }

    /**
     * Define uma template engine
     * @param string
     */
    public function engine($name, $callback){

        $this->engines[$name] = $callback;

    }

    /**
     * Define uma rota GET
     * @param string $router
     * @param Closure $callback
     */
    public function get(string $router, ...$callback){

    	if(count($callback) < 1){
    		return $this->options[$name];
    	}

        $this->router->get($router, ...$callback);
        return $this;

    }

    /**
     * Define uma rota POST
     * @param string $router
     * @param Closure $callback
     */
    public function post(string $router, ...$callback){

        $this->router->post($router, ...$callback);
        return $this;

    }

    /**
     * Define uma rota PUT
     * @param string $router
     * @param Closure $callback
     */
    public function put(string $router, ...$callback){

        $this->router->put($router, ...$callback);
        return $this;

    }

    /**
     * Define uma rota HEAD
     * @param string $router
     * @param Closure $callback
     */
    public function head(string $router, ...$callback){

        $this->router->head($router, ...$callback);
        return $this;

    }

    /**
     * Define uma rota DELETE
     * @param string $router
     * @param Closure $callback
     */
    public function delete(string $router, ...$callback){

        $this->router->delete($router, ...$callback);
        return $this;

    }

    /**
     * Define uma rota USE
     * @param mixed $d
     */
    public function use(...$d){

        $this->router->use(...$d);
        return $this;

    }

    /**
     * Define uma rota ALL
     * @param mixed $d
     */
    public function all(string $router, ...$callback){

        $this->router->all($router, ...$callback);
        return $this;

    }

    public function on($name, $func){
    	if(!isset($this->_events[$name])){
    		$this->_events[$name] = [];
    	}
        $this->_events[$name][] = $func;
    }

    public function emit($name, ...$params){

    	if(!isset($this->_events[$name])){
    		return;
    	}
    	foreach($this->_events[$name] as $func){
    		$func(...$params);
    	}

    }

    public function static($folder){

        return function ($req, $res, $next) use ($folder) {
            $path = $req->path;
            if(file_exists($folder."/".$path) && is_file($folder."/".$path)){
                $res->sendFile($folder."/".$path);
            }else{
                $next();
            }
        };

    }

    public function set($name, $value){
        $this->options[$name] = $value;
    }

    public function listen(){

        $this->router->listen($this);

    }

}