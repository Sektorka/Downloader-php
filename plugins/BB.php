<?php
/**
 * Created by PhpStorm.
 * User: GyKrisztian
 * Date: 2015. 10. 11.
 * Time: 12:50
 */

namespace plugins;

use includes\exceptions\DownloaderException;
use includes\IPlugin;
use includes\Item;
use includes\Version;

class BB extends IPlugin
{
    const DIR = "J:\\Zene\\BB";
    const URL = "http://88.151.102.138/hetvegi";
    private $version = null;

    public function getPluginName()
    {
        return "Barsi Balázs prédikációi";
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
       if(($error = $this->getHttpContent(self::URL, array($this, "gotContentData"), array($this, "gotHttpHeader"))) == 0){
            $this->decodeContent();

            if(preg_match_all('/(\\d+) <A HREF=\"\/hetvegi\/((.*?)\.mp3)\">/i', $this->content, $matches)){
                for($i = 0; $i < count($matches[0]); $i++){
                    $fileName = &$matches[2][$i];
                    $fileSize = &$matches[1][$i];
                    $filePath = self::DIR . DIRECTORY_SEPARATOR . $fileName;

                    $exists = (file_exists($filePath) && is_file($filePath));
                    $durl = self::URL . "/" . $fileName;

                    $item = new Item($fileName, $durl, $filePath, $fileSize, $this);
                    $item->setStatus($exists ? Item::STAT_SKIPPED : Item::STAT_IDLE);

                    $this->getManager()->addItem($item);
                }
            }
        }
        else{
            throw new DownloaderException(sprintf("Failed to get content from: %s", self::URL), $error);
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