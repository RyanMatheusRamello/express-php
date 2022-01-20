<?php

namespace ExpressPHP;


class Request {

    /**
     * Variavel do express
     * @var Express
     */
    public $app; 

    /**
     * Variavel que contem os dados POST/PUT/DELETE
     * @var object
     */
    public $body;

    /**
     * Variavel que contem os dados GET
     * @var object
     */
    public $query;

    /**
     * Variavel que contem os cookies da requisição
     * @var array
     */
    public $cookies;

    /**
     * Headers da requisição
     * @var array
     */
    public $headers;

    /**
     * Armazena o Host HTTP
     * @var string
     */
    public $hostname;

    /**
     * alias para $hostname
     */
    public $host;

    /**
     * Obtem o tipo da requisição
     * @var string
     */
    public $method;

    /**
     * Define os parametros da requisição
     */
    public $params;

    /**
     * Uma propriedade booleana que é verdadeira se o campo de cabeçalho X-Requested-With da solicitação for “XMLHttpRequest”, indicando que a solicitação foi emitida por uma biblioteca cliente como jQuery.
     * @var bool
     */
    public $xhr;

    public $routeUri;

    /**
     * inica a classe
     * @param Express
     */
    public function __construct(Express $app){
        
        $this->app = $app;
        $this->baseUrl = $_SERVER["DOCUMENT_ROOT"];
        if(!strcasecmp($_SERVER['REQUEST_METHOD'], 'DELETE')){
            parse_str(file_get_contents('php://input'), $this->body);
            if($this->body == null){
                $this->body = [];
            }
        }
        if(!strcasecmp($_SERVER['REQUEST_METHOD'], 'PUT')){
            parse_str(file_get_contents('php://input'), $this->body);
            if($this->body == null){
                $this->body = [];
            }
        }
        if(!strcasecmp($_SERVER['REQUEST_METHOD'], 'POST')){
            $this->body = $_POST;
        }
        $this->body = (object) $this->body;

        $this->query = (object) $_GET;
        $this->cookies = &$_COOKIE;
        $this->header = (object) (getallheaders() ?? []);
        $this->hostname = $_SERVER["HTTP_HOST"];
        $this->host = &$this->hostname;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->params = (object) [];
        if(isset($this->header->{"X-Requested-With"}) && $this->header->{"X-Requested-With"} == "XMLHttpRequest"){
            $this->xhr = true;
        }else{
            $this->xhr = false;
        }

    }

    public function is(string $type) : bool {

        if(isset($this->header->{"Content-Type"})){
            if(strpos($this->header->{"Content-Type"}, $type) === 0){
                return true;
            }
        }
        return false;

    }

    /**
     * Define os parametros no $this->params
     */
    public function __setParams(array $params){

        $this->params = (object) $params;

    }

}