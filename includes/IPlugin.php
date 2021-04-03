<?php

namespace includes;

use CurlHandle;
use JetBrains\PhpStorm\Pure;

abstract class IPlugin
{
    public abstract function getPluginName(): string;
    public abstract function getVersion(): Version;
    public abstract function getAuthorName(): string;
    public abstract function startSearch();
    public abstract function hasSettings(): bool;
    public abstract function showSettings();

    protected array|string $header = "";
    protected string $content = "";
    private Manager|null $manager;
    protected ?Version $version = null;
    protected int $responseCode = 200;

    #[Pure] public final function getCallableName(): bool|string
    {
        $class = get_class($this);
        $pos = strrpos($class, "\\");

        return ($pos === false ? $class : substr($class, $pos + 1));
    }

    public final function __construct(Manager $manager){
        $this->manager = $manager;
    }

    #[Pure] public function __toString(): string
    {
        return strval($this->getCallableName());
    }

    public static final function getInitedCurl($url, $arrayOptions = null): CurlHandle|resource
    {
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

    protected final function decodeContent(): bool
    {
        if(empty($this->header)){
            return false;
        }

        if(!array_key_exists('content-encoding', $this->header)){
            return false;
        }

        $arrContentEncoding = preg_split('/ /i', $this->header["content-encoding"]);

        if(is_array($arrContentEncoding)){
            if(in_array('gzip', $arrContentEncoding)){
                $this->content = gzdecode($this->content);
                return true;
            }
        }

        return false;
    }

    public final function getHttpContentLength($url): int
    {
        $this->header = "";
        $curl = self::getInitedCurl($url);

        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, "gotHttpHeader"));
        curl_setopt($curl, CURLOPT_NOBODY, true);

        curl_exec($curl);
        curl_close($curl);

        $this->header = Manager::parseHeader($this->header);

        if(is_array($this->header) && array_key_exists("content-length", $this->header)){
            $size = intval($this->header["content-length"]);
        }
        else{
            $size = 0;
        }

        return $size;
    }

    private function gotHttpHeader($curl, $headerLine): int
    {
        $this->header .= $headerLine;
        return strlen($headerLine);
    }

    private function gotContentData($curl, $data): int
    {
        $this->content .= $data;
        return strlen($data);
    }

    protected final function getHttpContent($url, $arrayOptions = null): int
    {
        $this->content = "";
        $this->header = "";

        $curl = self::getInitedCurl($url, $arrayOptions);

        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, "gotHttpHeader"));
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, array($this, "gotContentData"));

        curl_exec($curl);
        $this->responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $this->header = Manager::parseHeader($this->header);
        $return = curl_errno($curl);
        curl_close($curl);

        return $return;
    }

    /*public final function postHttpContent($url, $postData, $writeFunction, $headerFunction = null, $arrayOptions = null): int
    {
        $arrayOptions = array(
            CURLOPT_POST => is_array($postData),
            CURLOPT_POSTFIELDS => (is_array($postData) ? $postData : array())
        );

        return $this->getHttpContent($url, $arrayOptions);
    }*/

    protected function getManager(): Manager
    {
        return $this->manager;
    }
}