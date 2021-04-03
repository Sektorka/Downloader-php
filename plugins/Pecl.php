<?php

namespace plugins;

use includes\exceptions\DownloaderException;
use \includes\IPlugin;
use \includes\Item;
use \includes\Version;

class Pecl extends IPlugin
{
    const DIR = "D:\\Downloads\\php-extensions";
    const URL = "http://pecl.php.net";

    public function getPluginName(): string
    {
        return "Pecl";
    }

    public function getVersion(): Version
    {
        if($this->version == null){
            $this->version = new Version(1,0,0,0);
        }

        return $this->version;
    }

    public function getAuthorName(): string
    {
        return "Gyurász Krisztián";
    }

    public function startSearch()
    {
        $url = self::URL . "/package-stats.php";
        $tries = 5;

        if(($error = $this->getHttpContent($url)) == 0){
            $this->decodeContent();

            if(preg_match_all('/><a href=\"(\/package\/[[:alnum:][:punct:]]*)\">([[:alnum:][:punct:]]*)<\/a></i', $this->content, $matches)){
                for($i = 0; $i < count($matches[0]); $i++){
                    $name = &$matches[2][$i];
                    $uri = &$matches[1][$i];
                    $url = self::URL . $uri;
                    $dir = utf8_decode(self::DIR) . DIRECTORY_SEPARATOR . $name;

                    $this->getManager()->setCurrentSearch($name);

                    do{
                        if(($error = $this->getHttpContent($url)) == 0) {
                            $this->decodeContent();
                            if(preg_match_all('/<a href=\"\/get\/([[:alnum:][:punct:]]*\.tgz)\">/i', $this->content, $smatches)){
                                for($j = 0; $j < count($smatches[0]); $j++){
                                    $fileName = &$smatches[1][$j];
                                    $file = $dir . DIRECTORY_SEPARATOR . $fileName;
                                    $exists = (file_exists($file) && is_file($file));
                                    $durl = self::URL . "/get/" . $fileName;
                                    $fileSize = ($exists ? filesize($file) : $this->getHttpContentLength($durl));

                                    $item = new Item($fileName, $durl, $file, $fileSize, $this);
                                    $item->setStatus($exists ? Item::STAT_SKIPPED : Item::STAT_IDLE);

                                    $this->getManager()->addItem($item);
                                }
                            }
                            break;
                        }
                    }
                    while(--$tries > 0);

                    if($tries <= 0){
                        $this->getManager()->failOnSearch($name);
                    }
                }
            }
        }
        else{
            throw new DownloaderException(sprintf("Failed to get content from: %s", $url), $error);
        }
    }

    public function hasSettings(): bool
    {
        return false;
    }

    public function showSettings()
    {

    }
}