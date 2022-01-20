<?php

namespace ExpressPHP;

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

    private $mimet;
    private $mimetype;

    /**
     * função construtora
     * @param Express $app
     * @param Request $request
     */
    public function __construct(Express $app, Request $request){

        $this->app = $app;
        $this->req = $request;
        $this->locals = &$this->app->locals;
        $this->mimet = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'docx' => 'application/msword',
            'xlsx' => 'application/vnd.ms-excel',
            'pptx' => 'application/vnd.ms-powerpoint',
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $this->mimetype = array(
            'text/plain' => function($data){return $data; },
            'text/html' => function($data){return $data; },
            'text/css' => function($data){return $data; },
            'application/javascript' => function($data){return $data; },
            'application/json' => function($data){return json_encode($data); },
            'application/xml' => function($data){
                if(function_exists("express_xml_encode")){
                    return express_xml_encode($data)->asXML();
                }
                function express_xml_encode(mixed $value=null, string $key="root", SimpleXMLElement $parent=null){
                    if(is_object($value)) $value = (array) $value;
                    if(!is_array($value)){
                        if($parent === null){   
                            if(is_numeric($key)) $key = 'item';             
                            if($value===null) $node = new SimpleXMLElement("<$key />");
                            else              $node = new SimpleXMLElement("<$key>$value</$key>");
                        }
                        else{
                            $parent->addChild($key, $value);
                            $node = $parent;
                        }
                    }
                    else{
                        $array_numeric = false;
                        if($parent === null){ 
                            if(empty($value)) $node = new SimpleXMLElement("<$key />");
                            else              $node = new SimpleXMLElement("<$key></$key>");
                        }
                        else{
                            if(!isset($value[0])) $node = $parent->addChild($key);
                            else{
                                $array_numeric = true;
                                $node = $parent;
                            }
                        }
                        foreach( $value as $k => $v ) {
                            if($array_numeric) xml_encode($v, $key, $node);
                            else express_xml_encode($v, $k, $node);
                        }
                    }       
                    return $node;
                }
                return express_xml_encode($data)->asXML();
            },
            'application/x-shockwave-flash' => function($data){return $data; },
            'video/x-flv' => function($data){return $data; },
            // images
            'image/png' => function($data){return $data; },
            'image/jpeg' => function($data){return $data; },
            'image/gif' => function($data){return $data; },
            'image/bmp' => function($data){return $data; },
            'image/vnd.microsoft.icon' => function($data){return $data; },
            'image/tiff' => function($data){return $data; },
            'image/svg+xml' => function($data){return $data; },
            // archives
            'application/zip' => function($data){return $data; },
            'application/x-rar-compressed' => function($data){return $data; },
            'application/x-msdownload' => function($data){return $data; },
            'application/vnd.ms-cab-compressed' => function($data){return $data; },
            // audio/video
            'audio/mpeg' => function($data){return $data; },
            'video/quicktime' => function($data){return $data; },
            'video/quicktime' => function($data){return $data; },
            // adobe
            'application/pdf' => function($data){return $data; },
            'image/vnd.adobe.photoshop' => function($data){return $data; },
            'application/postscript' => function($data){return $data; },
            // ms office
            'application/msword' => function($data){return $data; },
            'application/rtf' => function($data){return $data; },
            'application/vnd.ms-excel' => function($data){return $data; },
            'application/vnd.ms-powerpoint' => function($data){return $data; },
            // open office
            'application/vnd.oasis.opendocument.text' => function($data){return $data; },
            'application/vnd.oasis.opendocument.spreadsheet' => function($data){return $data; },
        );

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
        $data = $this->mimetype[$this->headers["Content-Type"]]($data);
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
            
            if (isset( $this->mimet[$idx] )) {
                return $this->mimet[$idx];
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
        exit();
    }

    public function format($obj){

        foreach ($obj as $key => $value){

            $this->mimetype[$key] = $value;

        }

        return $this;

    }

    public function get($name){
        return $this->headers[$name] ?? null;
    }

    public function type($type){
        $type = $this->mimet[$type] ?? false;
        if($type == false){
            $typa = $this->mimetype[$type] ?? false;
            if($typa == false){
                $this->status(500);
                return $this->app->emit("error", $this->req, $this, new Error("Content-Type not acceptable from server", 500));
            }
            $type = $typa;
        }
        $this->set("Content-Type", $type);
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
        $options["maxAge"] = $options["maxAge"] ?? 0;
        $options["root"] = $options["root"] ?? "";
        $options["lastModified"] = $options["lastModified"] ?? true;
        $options["headers"] = $options["headers"] ?? [];
        $options["dotfiles"] = $options["dotfiles"] ?? "ignore";
        $options["cacheControl"] = $options["cacheControl"] = true;
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
            $this->set("Cache-Control", ($options["immutable"] ? "" : "immutable , ")."max-age=".$options["maxAge"]);
        }else{
            $this->set("Cache-Control", "no-cache, no-store, must-revalidate");
        }
        $this->send($content);
        return true;

    }

    public function render($name, $obj = []){

        $folder = $this->app->options["views"];
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
        $callback = function ($text){
            echo $text;
        };
        ob_start();
        $this->app->engines[$ext]($path, $file, $obj, $callback);
        $content = ob_get_contents();
        ob_end_clean();
        if($content == false){
            throw new \Error("No Content");
        }
        $this->send($content);
        return $this;

    }


}