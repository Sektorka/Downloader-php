<?php

namespace includes;

use \includes\exceptions\DownloaderException;
use \includes\Manager;
use \http\Header;

abstract class IPlugin
{
    public abstract function getPluginName();
    public abstract function getVersion();
    public abstract function getAuthorName();
    public abstract function startSearch();
    public abstract function hasSettings();
    public abstract function showSettings();

    protected $header = "";
    protected $content = "";
    private $manager = null;

    public function getCallableName(){
        $class = get_class($this);
        $pos = strrpos($class, "\\");

        return ($pos === false ? $class : substr($class, $pos + 1));
    }

    public final function __construct(Manager $manager){
        $this->manager = $manager;
    }

    private function setMainFrame($mainFrame){

    }

    public function __toString()
    {
        return $this->getCallableName();
    }

    public static final function getInitedCurl($url, $arrayOptions = null){
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_HTTP_VERSION,   CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_URL,            $url);
        curl_setopt($curl, CURLOPT_USERAGENT,      USER_AGENT);
        curl_setopt($curl, CURLOPT_COOKIEFILE,     COOKIES_FILE);
        curl_setopt($curl, CURLOPT_COOKIEJAR,      COOKIES_FILE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_TIMEOUT,        10000);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Accept-Encoding:gzip, deflate',
            'Connection:keep-alive'
        ));

        if(PROXY_ENABLED){
            curl_setopt($curl, CURLOPT_PROXY, PROXY_HOST . ":" . PROXY_PORT);
            if(PROXY_AUTHENTICATION){
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, PROXY_USERNAME . ":" . PROXY_PASSWORD);
            }
        }

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_VERBOSE,        DEBUG);

        if(isset($arrayOptions) && is_array($arrayOptions) && count($arrayOptions) > 0) {
            curl_setopt_array($curl, $arrayOptions);
        }

        return $curl;
    }

    protected final function parseHeader(){
        $this->header = array_change_key_case(Header::parse($this->header));    }

    protected final function decodeContent(){
        if(empty($this->header)){
            return false;
        }

        $header = array_change_key_case(Header::parse($this->header));

        if(!array_key_exists('content-encoding', $header)){
            return false;
        }

        $arrContentEncoding = preg_split('/ /i', $header["content-encoding"]);

        if(is_array($arrContentEncoding)){
            if(in_array('gzip', $arrContentEncoding)){
                $this->content = gzdecode($this->content);
                return true;
            }
        }

        return false;
    }

    public final function getHttpContentLength($url){
        $this->header = "";
        $curl = self::getInitedCurl($url);

        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, "gotHttpHeader"));
        curl_setopt($curl, CURLOPT_NOBODY, true);

        curl_exec($curl);
        curl_close($curl);

        $this->parseHeader();

        if(is_array($this->header) && array_key_exists("content-length", $this->header)){
            $size = intval($this->header["content-length"]);
        }
        else{
            $size = 0;
        }

        return $size;
    }

    protected function gotHttpHeader($curl, $headerLine){
        $this->header .= $headerLine;
        return strlen($headerLine);
    }

    protected function gotContentData($curl, $data){
        $this->content .= $data;
        return strlen($data);
    }

    public final function getHttpContent($url, $writeFunction, $headerFunction = null, $arrayOptions = null){
        $this->content = "";
        $this->header = "";

        $curl = self::getInitedCurl($url, $arrayOptions);

        if(isset($headerFunction) && $headerFunction != null && is_callable($headerFunction)){
            curl_setopt($curl, CURLOPT_HEADERFUNCTION, $headerFunction);
        }

        if(isset($writeFunction) && $writeFunction != null && is_callable($writeFunction)) {
            curl_setopt($curl, CURLOPT_WRITEFUNCTION, $writeFunction);
        }
        else{
            throw new DownloaderException((is_array($writeFunction) ? implode("::", $writeFunction) : $writeFunction) . " is not callable.");
        }

        curl_exec($curl);
        $return = curl_errno($curl);
        curl_close($curl);

        return $return;
    }

    public final function postHttpContent($url, $postData, $writeFunction, $headerFunction = null, $arrayOptions = null){
        $arrayOptions = array(
            CURLOPT_POST => is_array($postData),
            CURLOPT_POSTFIELDS => (is_array($postData) ? $postData : array())
        );

        return $this->getHttpContent($url, $writeFunction, $headerFunction, $arrayOptions);
    }

    protected function getManager()
    {
        return $this->manager;
    }

    protected $running = false;
    private $mainFrame = null;

}