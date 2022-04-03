<?php

namespace ExpressPHP\Static;

class MimeTypes {

	static private $mimet;
	static private $mimetype;

	public function get($name, $sname=false){
		if($sname){
			if(isset(self::$mimetype[$name])){
				return $name;
			}
			return self::$mimet[$name] ?? null;
		}
		if(isset(self::$mimetype[$name])){
			return self::$mimetype[$name];
		}
		return self::$mimetype[self::$mimet[$name]] ?? null;
	}

	public static function addExtension(string $ext, callable $mime){
		self::$mimet[$ext] = $mime;
	}

	public static function addMimeType(string $mime, callable $callable){
		self::$mimetype[$mime] = $callable;
	}

	static public function init(){

		self::$mimet = array(
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

        self::$mimetype = array(
            'text/plain' => function($data){return $data; },
            'text/html' => function($data){return $data; },
            'text/css' => function($data){return $data; },
            'application/javascript' => function($data){return $data; },
            'application/json' => function($data){
                if(is_array($data) || is_object($data)){
                    return json_encode($data);
                }
                $result = json_decode($data);
                if(json_last_error() === JSON_ERROR_NONE){
                    return $data;
                }
                return json_encode($data); 
            },
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
                            if($array_numeric) express_xml_encode($v, $key, $node);
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

}