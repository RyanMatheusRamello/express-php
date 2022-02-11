<?php

namespace ExpressPHP;

use \Error;

class Router {

    private $app;
    private $request;
    private $response;
    private $no_404;
    private $routerActual = [];

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

    private function addRouter($method, $uri, $func){

        preg_match_all("/\:([^\/]+)/m", $uri, $matches, PREG_SET_ORDER, 0);

        if($method == "USE"){
            $muri = str_replace("*", "(.*)?", $uri);
        }else{
            $muri = str_replace("*", "(.*)", $uri);
        }
        $str = preg_replace("/\:([^\/]+)/m", '([^/]+)', $muri);
        $uri_regex = '/^'.str_replace('/', '\/', $str).'\/?$/';

        $arm = [
            "uri" => $uri,
            "params" => [],
            "uri_regex" => $uri_regex,
            "function" => $func
        ];

        if($this->no_404 == true){
            $this->no_404 = false;
            $arm["no_404_error"] = true;
        }

        foreach ($matches as $value){
            $arm["params"][] = [
                $value[1]
            ];
        }

        $this->routers[$method][] = $arm;

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
            }else{
                foreach ($data as $func){
                    $this->addRouter("USE", $uri, $func);
                }
                $uri .= "/*";
            }
        }
        foreach ($data as $func){
            $this->addRouter("USE", $uri, $func);
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

        if(count($callback) > 0){
            $this->use("/", ...$callback);
        }

        $this->addRouter("GET", $uri, $func);

    }

    public function post(string $uri, ...$callback){

        if(count($callback) == 0){
            throw new Error("Callback not defined");
        }

        $func = array_pop($callback);

        if(count($callback) > 0){
            $this->use("/", ...$callback);
        }

        $this->addRouter("POST", $uri, $func);

    }

    public function put(string $uri, ...$callback){

        if(count($callback) == 0){
            throw new Error("Callback not defined");
        }

        $func = array_pop($callback);

        if(count($callback) > 0){
            $this->use("/", ...$callback);
        }

        $this->addRouter("PUT", $uri, $func);

    }

    public function head(string $uri, ...$callback){

        if(count($callback) == 0){
            throw new Error("Callback not defined");
        }

        $func = array_pop($callback);

        if(count($callback) > 0){
            $this->use("/", ...$callback);
        }

        $this->addRouter("HEAD", $uri, $func);

    }

    public function delete(string $uri, ...$callback){

        if(count($callback) == 0){
            throw new Error("Callback not defined");
        }

        $func = array_pop($callback);

        if(count($callback) > 0){
            $this->use("/", ...$callback);
        }

        $this->addRouter("DELETE", $uri, $func);

    }

    public function all(...$data){

        $this->get(...$data);
        $this->put(...$data);
        $this->delete(...$data);
        $this->head(...$data);
        $this->post(...$data);

    }

    public function listen(){

        try {

            $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

            $i = false;

            foreach ($this->routers[$_SERVER['REQUEST_METHOD']] as $router){

                if(preg_match($router["uri_regex"], $uri, $matches)){
                    $i = true;
                }

            }

            foreach ($this->routers["USE"] as $router){

                if(preg_match($router["uri_regex"], $uri, $matches)){
                    $i = true;
                }

            }

            if($i !== true){
                return $this->app->emit("error", $this->request, $this->response, new Error("Not Found", 404));
            }

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

                    //$this->request->__setParams($param);
                    $this->routerActual[] = [
                        "use" => true,
                        "param" => $param,
                        "router" => $router
                    ];
                    //$router["function"]($this->request, $this->response, $next);

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

                    //$this->request->__setParams($param);
                    //$router["function"]($this->request, $this->response);
                    $this->routerActual[] = [
                        "use" => false,
                        "param" => $param,
                        "router" => $router
                    ];

                }

            }

            $this->processRouter();

        } catch (Error $error){
            $this->app->emit("error", $this->request, $this->response, $error);
        }

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
        $router = array_shift($this->routerActual);
        $data = $this;
        $this->request->__setParams($router["param"]);
        $next = function($d=null) use($data){
            $data->processRouter($d);
        };
        if($router["use"]){
            $router["router"]["function"]($this->request, $this->response, $next);
        }else{
            $router["router"]["function"]($this->request, $this->response);
        }

    }

}