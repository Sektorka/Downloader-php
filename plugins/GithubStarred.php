<?php


namespace plugins;


use includes\exceptions\DownloaderException;
use includes\IPlugin;
use includes\Item;
use includes\Version;

class GithubStarred extends IPlugin
{
    const DIR = "E:\\Downloads\\Github-starred";
    const URL = "https://api.github.com/users/Sektorka/starred?page=%d";
    const DOWNLOAD = "https://codeload.github.com/%s/zip/master";
    private $version = null;

    public function getPluginName()
    {
        return "GithubStarred";
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
        echo "Starting search Item\n";
        $page = 0;

        do{
            $url = sprintf(self::URL, $page++);

            if(($error = $this->getHttpContent($url, array($this, "gotContentData"), array($this, "gotHttpHeader"))) == 0){
                $this->decodeContent();
                $json = json_decode($this->content);



                if(count($json) == 0){
                    echo "No downloadable content.\n";
                    break;
                }

                echo "Count: " . count($json) . "\n";

                foreach($json as $index => $star){
                    $fileName = str_replace("/","-", $star->full_name) . "-" . $star->default_branch . ".zip";
                    $filePath = self::DIR . $fileName;
                    $durl = sprintf(self::DOWNLOAD, $star->full_name);
                    $fileSize = $this->getHttpContentLength($durl);
                    $exists = (file_exists($filePath) && is_file($filePath) && $fileSize == filesize($filePath));

                    $item = new Item($fileName, $durl, $filePath, $fileSize, $this);
                    $item->setStatus($exists ? Item::STAT_SKIPPED : Item::STAT_IDLE);

                    $this->getManager()->addItem($item);
                }

            }
            else{
                throw new DownloaderException(sprintf("Failed to get content from: %s", $url), $error);
            }
        }
        while(true);
    }

    public function hasSettings()
    {
        return false;
    }

    public function showSettings()
    {

    }
}