<?php

namespace ExpressPHP;

use \Error;

class Router {

	private $routerActual = [];
    private $routersNOUSER = 0;

    public $routers = [
        "GET" => [],
        "POST" => [],
        "PUT" => [],
        "DELETE" => [],
        "HEAD" => [],
        "USE" => [],
        "ALL" => []
    ];

	public function __construct(Express $app, Request $request, Response $response){

		$this->app = $app;
        $this->request = $request;
        $this->response = $response;

    }

    private function addRouter($method, $uri, $call, $func){

    	//echo "<b> $method </b> - $uri <br>";

    	preg_match_all("/\:([^\/]+)/m", $uri, $matches, PREG_SET_ORDER, 0);

        if($method == "USE"){
            $muri = str_replace("*", "(.*)", $uri);
        }else{
            $muri = str_replace("*", "(.*)", $uri);
        }
        $str = preg_replace("/\:([^\/]+)/m", '([^/]+)', $muri);
        $uri_regex = '/^'.str_replace('/', '\/', $str).'\/?$/';

        $arm = [
            "uri" => $uri,
            "params" => [],
            "uri_regex" => $uri_regex,
            "function" => $func,
            "middlewares" => $call,
        ];

        foreach ($matches as $value){
            $arm["params"][] = [
                $value[1]
            ];
        }

        $this->routers[$method][] = $arm;
    }

    public function listen(){
    	ob_start();
    	try {

            $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
            foreach ($this->routers["USE"] as $router){

                if(preg_match($router["uri_regex"], $uri, $matches)){

                    preg_match($router["uri_regex"], $uri, $params);

                    $semNome = 0;

                    array_shift($params);

                    if(count($router["params"]) > 0){
                        foreach ($params as $key => $value){
    
                            if(count($router["params"][intval($key)]) == 0){
                                $router["params"][intval($key)][] = $semNome;
                                $semNome++;
                            }
                            $router["params"][intval($key)][] = $value;
    
                        }
                    }else{
                        if(count($params) > 0){
                            foreach ($params as $key => $value){
                                $router["params"][intval($key)] = [];
                                $router["params"][intval($key)][] = $semNome;
                                $semNome++;
                                $router["params"][intval($key)][] = $value;
                            }
                        }
                    }

                    $param = [];
                    foreach($router["params"] as $value){
                        $param[$value[0]] = $value[1];
                    }
                    $this->routerActual[] = [
                        "use" => true,
                        "param" => $param,
                        "router" => $router
                    ];

                }

            }

            $k = false;

            

            foreach ($this->routers[$_SERVER['REQUEST_METHOD']] as $router){

                if(preg_match($router["uri_regex"], $uri, $matches)){

                    if($k == true){
                        break;
                    }
                    $k = true;

                    preg_match($router["uri_regex"], $uri, $params);

                    $semNome = 0;

                    array_shift($params);

                    if(count($router["params"]) > 0){
                        foreach ($params as $key => $value){
    
                            if(count($router["params"][intval($key)]) == 0){
                                $router["params"][intval($key)][] = $semNome;
                                $semNome++;
                            }
                            $router["params"][intval($key)][] = $value;
    
                        }
                    }else{
                        if(count($params) > 0){
                            foreach ($params as $key => $value){
                                $router["params"][intval($key)] = [];
                                $router["params"][intval($key)][] = $semNome;
                                $semNome++;
                                $router["params"][intval($key)][] = $value;
                            }
                        }
                    }

                    $param = [];
                    foreach($router["params"] as $value){
                        $param[$value[0]] = $value[1];
                    }
                    $this->routerActual[] = [
                        "use" => false,
                        "param" => $param,
                        "middlewares" => $router["middlewares"],
                        "router" => $router
                    ];

                }

            }

            $this->processRouter();

        } catch (Error $error){
            $this->app->emit("error", $this->request, $this->response, $error);
        }
        $content = ob_get_contents();
	    ob_end_clean();

        if(strlen($content) > 0){
            echo $content;
        }else{
            if($this->routersNOUSER < 1){
                $this->app->emit("error", $this->request, $this->response, new Error("Not Found", 404));
            }else{
                $this->response->status(204);
            }
        }
	    exit();
    }


    public function processRouter($error = null){

        if(!is_null($error)){
            if($error instanceof Error){
                throw $error;
            }else{
                throw new Error($error);
            }
        }
        if(count($this->routerActual) == 0){
            return;
        }
        $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

        if($this->routerActual[0]["use"]){
        	$router = array_shift($this->routerActual);
	        $data = $this;
	        $this->request->__setParams($router["param"]);
	        $this->request->__setPath("/".ltrim($router["param"][0], "/") ?? "/", $path);
	        $next = function($d=null) use($data){
	            $data->processRouter($d);
	        };
            $router["router"]["function"]($this->request, $this->response, $next);
        }else{
        	if(count($this->routerActual[0]["middlewares"]) > 0){
        		$call = array_shift($this->routerActual[0]["middlewares"]);
		        $data = $this;
		        $this->request->__setParams($router["param"] ?? []);
		        $this->request->__setPath("/".ltrim($router["param"][0], "/") ?? "/", $path);
		        $next = function($d=null) use($data){
		            $data->processRouter($d);
		        };
		        $call($this->request, $this->response, $next);
        	}else{
                $this->routersNOUSER++;
        		$router = array_shift($this->routerActual);
		        $data = $this;
		        $this->request->__setParams($router["param"] ?? []);
		        $this->request->__setPath($path, $path);
		        $next = function($d=null) use($data){
		            $data->processRouter($d);
		        };
        		$router["router"]["function"]($this->request, $this->response);
        	}
        }

    }

    public function use(...$data){
        if(count($data) == 0){
            throw new Error("Callback not defined");
        }
        $uri = "/*";
        if(is_string($data[0])){
            $uri = array_shift($data);
        }
        if(substr($uri, -1) !== "*"){
            if(substr($uri, -1) === "/"){
                $uri .= "*";
                foreach ($data as $func){
		            //$this->addRouter("USE", $uri, [], $func);
		        }
            }else{
            	$uri .= "(/*|)";
            }
        }
        foreach ($data as $func){
            $this->addRouter("USE", $uri, [], $func);
        }
        $uri = rtrim($uri, "/");
        if($uri == ""){
            $uri = "/*";
        }
        
    }

    public function get(string $uri, ...$callback){

        if(count($callback) == 0){
            throw new Error("Callback not defined");
        }

        $func = array_pop($callback);
        $mid = [];

        if(count($callback) > 0){
            $mid = [...$callback];
        }

        $this->addRouter("GET", $uri, $mid, $func);

    }

    public function post(string $uri, ...$callback){

        if(count($callback) == 0){
            throw new Error("Callback not defined");
        }

        $func = array_pop($callback);

        $mid = [];

        if(count($callback) > 0){
            $mid = [...$callback];
        }

        $this->addRouter("POST", $uri, $mid, $func);

    }

    public function put(string $uri, ...$callback){

        if(count($callback) == 0){
            throw new Error("Callback not defined");
        }

        $func = array_pop($callback);

        $mid = [];

        if(count($callback) > 0){
            $mid = [...$callback];
        }

        $this->addRouter("PUT", $uri, $mid, $func);

    }

    public function head(string $uri, ...$callback){

        if(count($callback) == 0){
            throw new Error("Callback not defined");
        }

        $func = array_pop($callback);

        $mid = [];

        if(count($callback) > 0){
            $mid = [...$callback];
        }

        $this->addRouter("HEAD", $uri, $mid, $func);

    }

    public function delete(string $uri, ...$callback){

        if(count($callback) == 0){
            throw new Error("Callback not defined");
        }

        $func = array_pop($callback);

        $mid = [];

        if(count($callback) > 0){
            $mid = [...$callback];
        }

        $this->addRouter("DELETE", $uri, $mid, $func);

    }

    public function all(...$data){

        $this->get(...$data);
        $this->put(...$data);
        $this->delete(...$data);
        $this->head(...$data);
        $this->post(...$data);

    }

}