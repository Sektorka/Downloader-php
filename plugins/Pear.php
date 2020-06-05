<?php

namespace plugins;

use includes\exceptions\DownloaderException;
use \includes\IPlugin;
use \includes\Item;
use \includes\Version;

class Pear extends IPlugin
{
    const DIR = "E:\\Downloads\\php-pear-packages";
    const URL = "https://pear.php.net";
	const DOWNLOAD_URL = "https://download.pear.php.net";
    private $version = null;

    public function getPluginName()
    {
        return "Pear";
    }

    public function getVersion()
    {
        if($this->version == null){
            $this->version = new Version(1,0,0,0);
        }

        return $this->version;
    }

    public function getAuthorName()
    {
        return "Gyurász Krisztián";
    }

    public function startSearch()
    {
        $url = self::URL . "/package-stats.php";
        $tries = 5;

        if(($error = $this->getHttpContent($url, array($this, "gotContentData"), array($this, "gotHttpHeader"))) == 0){
            //$this->decodeContent($this->content, $this->header);
            $this->decodeContent();

            if(preg_match_all("/<a href=\"\/package\/(.*?)\">/i", $this->content, $matches)){
				foreach($matches[1] as $packageName){
					$uri = "/package/" . $packageName . "/download/";
					$url = self::URL . $uri;
                    $dir = utf8_decode(self::DIR) . DIRECTORY_SEPARATOR . $packageName;
					$this->getManager()->setCurrentSearch($packageName);
					
					do{
						if(($error = $this->getHttpContent($url, array($this, "gotContentData"), array($this, "gotHttpHeader"))) == 0){
							$this->decodeContent($this->content, $this->header);
							
							if(preg_match_all("/<a href=\"" . preg_quote($uri, '/') . "(.*?)\">/i", $this->content, $smatches)){
								foreach($smatches[1] as $packageVersion){
									$fileName = $packageName . "-" . $packageVersion . ".tgz";
									$file = $dir . DIRECTORY_SEPARATOR . $fileName;
									$exists = (file_exists($file) && is_file($file));
									$durl = self::DOWNLOAD_URL . "/package/" . $fileName;
									$fileSize = ($exists ? filesize($file) : $this->getHttpContentLength($durl));

									$item = new Item($fileName, $durl, $file, $fileSize, $this);
									$item->setStatus($exists ? Item::STAT_SKIPPED : Item::STAT_IDLE);

									$this->getManager()->addItem($item);
								}
							}
							break;
						}
						else{
							throw new DownloaderException(sprintf("Failed to get content from: %s", $url), $error);
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

    public function hasSettings()
    {
        return false;
    }

    public function showSettings()
    {

    }
}