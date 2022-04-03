<?php

namespace ExpressPHP;

use \Error;

class Response {

    /**
     * Variavel do express
     * @var Express
     */
    public $app;

    /**
     * referencia a variavel $this->app->locals
     * @var object
     */
    public $locals;

    /**
     * Variavel Request
     */
    public $req;

    /**
     * Armazena os headers da resposta
     * @var array
     */
    private $headers = [];

    /**
     * Propriedade booleana que indica se o aplicativo enviou cabeçalhos HTTP para a resposta.
     * @var bool
     */
    public $headersSent = false;

    private $mime;

    /**
     * função construtora
     * @param Express $app
     * @param Request $request
     */
    public function __construct(Express $app, Request $request){

        $this->app = $app;
        $this->req = $request;
        $this->locals = &$this->app->locals;
        $this->mime = new \ExpressPHP\Static\MimeTypes();
        

    }

    private function __sendHeaders(){

        if($this->headersSent == false){
            
            foreach ($this->headers as $key => $value){

                header($key.": ".$value);

            }

            $this->headersSent == true;

        }

    }

    /**
     * Envia algo para o usúario
     * @param mixed $data
     */
    public function send($data){

        $this->__sendHeaders();

        if(is_object($data) || is_array($data)){
            if(!isset($this->headers["Content-Type"])){
                if(is_object($data)){
                    $data = get_object_vars($data);
                }
                @$this->type("json");
                $this->send($data);
                return $this;
            }
        }

        if(!isset($this->headers["Content-Type"])){
            $this->type("html");
        }
        $mime = $this->mime->get($this->headers["Content-Type"]);
        if($mime){
            $data = $mime($data);
        }
        echo $data;
        return $this;

    }

    /**
     * Define o campo de cabeçalho Content-Disposition da resposta HTTP para “attachment”. Se um nome de arquivo for fornecido, ele definirá o Content-Type com base no nome da extensão por meio de $res->type() e definirá o parâmetro Content-Disposition “filename=".
     * @param string
     */
    public function attachment(string $filename, $name = null){

        if(file_exists($filename)){
            $contentType = $this->__getMimeType($filename);
            $this->set("Content-Type", $contentType);
            $this->set("Content-Disposition", "attachment; filename=".($name ?? basename($filename)));
            $handle = fopen($filename, "r");
            $content = fread($handle, filesize($handle));
            fclose($handle);
            $this->send($content);
            return true;
        }else{
            return false;
        }

    }

    private function __getMimeType($filename){
            $idx = explode( '.', $filename );
            $count_explode = count($idx);
            $idx = strtolower($idx[$count_explode-1]);
            $mime = $this->mime->get($idx, true);
            if ($mime) {
                return $mime;
            } else {
                return 'application/octet-stream';
            }
    }

    /**
     * Envia algo para o usúario
     * @param mixed $json
     */
    public function json($json){

        $this->type("json");
        return $this->send(json_encode($json));

    }

    public function status(int $status){
        http_response_code($status);
        return $this;
    }

    public function location(string $uri, bool $end = true){
        $this->set("Location", $uri);
        if($end){
            $this->__sendHeaders();
            exit();
        }
        return $this;
    }

    public function cookie($name, $value, ?array $options){

        setcookie($name, $value, $options["expires"] ?? 0, $options["path"] ?? "", $options["domain"] ?? "", $options["secure"] ?? false, $options["httponly"] ?? false);
        return $this;

    }

    public function set($name, $value = null){
        if(is_null($value)){
            if(is_array($name)){
               foreach ($name as $key => $val){
                $this->headers[$key] = $val;
               }
               return $this;
            }
            throw new Error("Value not defined");
        }
        $this->headers[$name] = $value;
        return $this;
    }


    public function clearCookie($name, $options = ["path" => "", "domain" => ""]){
        setcookie($name, "", $options["path"], $options["domain"]);
        unset($_COOKIE[$name]);
        return $this;
    }

    public function end($data = ""){
        if($data !== ""){
            $this->send($data);
        }else {
            $this->__sendHeaders();
        }
        $content = ob_get_contents();
        ob_end_clean();

        if(strlen($content) > 0){
            echo $content;
        }else{
            $this->status(204);
        }
        exit();
    }

    public function format($obj){


        foreach ($obj as $key => $value){

            \ExpressPHP\Static\MimeTypes::addMimeType($key, $value);

        }

        return $this;

    }

    public function get($name){
        return $this->headers[$name] ?? null;
    }

    public function type($type){

        $type = $this->mime->get($type, true);
        if(!$type){
            $this->status(500);
                return $this->app->emit("error", $this->req, $this, new Error("Content-Type not acceptable from server", 500));
        }
        $this->set("Content-Type", $type);
        return $this;
    }

    public function jsonp($data){

        $name = $this->app->options["jsonp callback name"] ?? "callback";
        $cb = $this->req->query->$name ?? "callback";
        $this->set("Content-Type", "application/javascript");
        $this->send($cb."(".json_encode($data).")");
        return $this;

    }

    public function redirect($code, $uri = null, $end = true){

        if(is_null($uri)){
            $uri = $code;
            $code = 302;
        }
        $this->status($code);
        $this->location($uri, $end);
        return $this;

    }

    public function sendStatus(int $code){

        $this->status($code);
        return $this;

    }

    public function sendFile($filename, $options = [
        "maxAge" => 0,
        "root" => "",
        "lastModified" => true,
        "headers" => [],
        "dotfiles" => "ignore",
        "acceptRanges" => true,
        "cacheControl" => true,
        "immutable" => false
    ], $callback = null){

        if(is_callable($options)){
            $callback = $options;
            $options = [];
        }
        $options["maxAge"] = $options["maxAge"] ?? 1;
        $options["root"] = $options["root"] ?? "";
        $options["lastModified"] = $options["lastModified"] ?? true;
        $options["headers"] = $options["headers"] ?? [];
        $options["dotfiles"] = $options["dotfiles"] ?? "ignore";
        $options["cacheControl"] = $options["cacheControl"] ?? false;
        $options["immutable"] = $options["immutable"] = false;
        if(!file_exists($options["root"].$filename)){
            if(is_callable($callback)){
                $callback(new Error("File Not Found"));
                return false;
            }
            return false;
        }
        if($options["dotfiles"] != "allow" && $options["dotfiles"] != "ignore"){
            if(strpos(".", basename($options["root"].$filename, PATHINFO_EXTENSION)) == 0){
                if(is_callable($callback)){
                    $callback(new Error("Dot File not allowed"));
                    return false;
                }
                return false;
            }
        }
        $handle = fopen($options["root"].$filename, "r");
        $content = fread($handle, filesize($options["root"].$filename));
        fclose($handle);
        $contentType = $this->__getMimeType($options["root"].$filename);
        $this->set("Content-Type", $contentType);
        if($options["cacheControl"]){
            $this->set("Cache-Control", $options["cacheControl"] . ", "($options["immutable"] ? "" : "immutable , ")."max-age=".$options["maxAge"]);
        }else{
            $this->set("Cache-Control", "no-cache, no-store, must-revalidate");
        }
        $this->send($content);
        return true;

    }

    private $render_content;

    public function render($name, $obj = []){

        $folder = $this->app->get("views");
        $parts = [$folder, $name];
        if (sizeof($parts) === 0){
            $joined = "";
        }else{
            $prefix = ($parts[0] === DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '';
            $processed = array_filter(array_map(function ($part) {
                return rtrim($part, DIRECTORY_SEPARATOR);
            }, $parts), function ($part) {
                return !empty($part);
            });
            $joined = $prefix . implode(DIRECTORY_SEPARATOR, $processed);
        }

        $file = basename($joined);
        $path = dirname($joined);
        $a = explode(".", $file);
        if(count($a) > 1){
            $ext = array_pop($a);
        }else{
            $ext = $this->app->options["view engine"];
            $file .= ".".$ext;
        }

        if(!isset($this->app->engines[$ext])){
            throw new \Error("Engine not found");
        }
        $data = $this;
        $this->render_content = "";
        $callback = function ($text) use ($data){
            $data->render_content = $text;
        };
        
        $this->app->engines[$ext]($path, $file, $obj, $callback);
        if($this->render_content !== ""){
            $this->send($this->render_content);   
        }
        return $this;

    }


}