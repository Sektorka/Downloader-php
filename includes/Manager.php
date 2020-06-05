<?php

namespace includes;

require_once "config.php";

use \includes\exceptions\DownloaderException;
use \includes\exceptions\InvalidArgumentException;
use \DirectoryIterator;
use \SplFileInfo;

final class Manager{
    const PLUGINS_DIR = "plugins";

    private $plugins, $arguments, $usePlugin;
    private static $initialized, $instance, $terminalColumns;
    private $items = array();
    private $notDownloadedItems = array();
    private $file;
    private $currentSearchName, $currentDownloadItem;
    private $downloading = false, $downloadedItemsCount = 0;

    private function __construct($argv)
    {
        register_shutdown_function(array($this, "shutdown"));
        register_tick_function(array($this, "tick"));
        declare(ticks=300);

        $this->arguments = $argv;
        $this->loadPlugins();
        $this->parseArguments();
    }

    public function addItem(Item $item){
        $this->items[] = $item;

        if($item->getStatus() == Item::STAT_IDLE){
            $this->notDownloadedItems[] = $item;
        }

        //echo sprintf("%s - %s - %s\r\n", $item->getPlugin()->getPluginName(), $item->getFileName(), $item->getStatusStr());
    }

    public function failOnSearch($name){
        $this->setCurrentSearch($name, true);
    }

    public static function initialize($argv){
        if(!extension_loaded("http")){
            throw new DownloaderException("pecl_http php extension does not installed! Download from: https://pecl.php.net/package/pecl_http", 13);
        }

		self::$terminalColumns = self::getTerminalSizeOnWindows()["width"];
        self::$initialized = true;
        self::$instance = new Manager($argv);
    }

    public static function getInstance()
    {
        if(!self::$initialized){
            throw new DownloaderException("First call initialize method!");
        }

        return self::$instance;
    }

    public function getPlugins()
    {
        return $this->plugins;
    }

    private function parseArguments(){
        if(count($this->arguments) > 1 && preg_match('/\-\-plugin=(\w+)/i', $this->arguments[1], $plugin)){
            foreach($this->plugins as $plug){
                if($plug instanceof IPlugin){
                    if($plugin[1] == $plug->getCallableName()){
                        $this->usePlugin = $plugin[1];
                        return;
                    }
                }
            }
        }

        throw new InvalidArgumentException(
            "Please choose exists plugin with that argument: --plugin=[PLUGIN NAME]\n".
            "Available plugins: " . implode(", ", $this->plugins));
    }

    private function loadPlugins(){
        if(!file_exists(self::PLUGINS_DIR) || !is_dir(self::PLUGINS_DIR)){
            throw new DownloaderException(self::PLUGINS_DIR . " path does not exists.");
        }

        if(!is_readable(self::PLUGINS_DIR)){
            throw new DownloaderException(self::PLUGINS_DIR . " path is not readable.");
        }

        $this->plugins = array();

        foreach(new DirectoryIterator(self::PLUGINS_DIR) as $file){
            if($file->isDot() || !($file instanceof SplFileInfo)) continue;

            require_once self::PLUGINS_DIR . DIRECTORY_SEPARATOR . $file->getFilename();

            $pluginClass = "\\plugins\\" . basename($file->getFilename(), ".php");
            $this->plugins[] = new $pluginClass($this);
        }


    }

    public function startSearch($autoStartDownload = false){
        foreach($this->plugins as $plugin){
            if($plugin instanceof IPlugin && $plugin == $this->usePlugin){
                $plugin->startSearch();
            }
        }

        if($autoStartDownload && count($this->notDownloadedItems) > 0){
            $this->startDownload();
        }
    }

    public function startDownload(){
        $this->downloading = true;

        foreach($this->notDownloadedItems as $item){
            if($item instanceof Item){
                $this->downloadItem($item);
                $this->downloadedItemsCount++;
            }
        }
    }

    public function downloadItem(Item $item){
        echo "\n";
        $this->currentDownloadItem = $item;
        $curl = IPlugin::getInitedCurl($item->getUrl());

        $dir = dirname($item->getDestination());
        if(!file_exists($dir) || !is_dir($dir)){
            if(!mkdir($dir, 0777, true)){
                throw new DownloaderException("Failed to create dir: " . $dir);
            }
        }

        if(($this->file = fopen($item->getDestination(), 'w')) === false){
            throw new DownloaderException("Failed to create file for write: " . $item->getDestination());
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($curl, CURLOPT_BINARYTRANSFER, true); //deprecated
        curl_setopt($curl, CURLOPT_NOPROGRESS,     false);
        curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, array($this, "progressFunction"));
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, array($this, "writeFunction"));

        curl_exec($curl);
        curl_close($curl);
        fclose($this->file);
    }

    public function setCurrentSearch($name, $fail = false){
        if($this->downloading){
            return;
        }

        $this->currentSearchName = $name;
        $strSearching = str_pad(
            sprintf("\rNot downloaded items: %' 4d (size: %' 9s) | Searching%s %s%s",
                count($this->notDownloadedItems),
                self::formatBytes($this->getTotalSizeOfNotDownloadedItems()),
                $this->getDots(),
                $name,
                $fail ? " !!! Failed to load content!!!" : ""
            )
            , self::$terminalColumns, " "
        );

		echo $strSearching . ($fail ? "\n" : "");
		
		if($fail){
            sleep(2);
        }
    }

    private function getDots(){
        switch(time() % 4){
            case 1:
                return ".  ";
            case 2:
                return ".. ";
            case 3:
                return "...";
            default:
                return "   ";
        }
    }

    private function getTotalSizeOfNotDownloadedItems(){
        $totalSize = 0;

        foreach($this->notDownloadedItems as $item){
            if($item instanceof Item){
                $totalSize += $item->getFileSize();
            }
        }

        return $totalSize;
    }

    private function writeFunction($curl, $data){
        if(is_resource($this->file)){
            return fwrite($this->file, $data);
        }

        return 0;
    }

    private function progressFunction($curl, $downloadSize, $downloaded, $uploadSize, $uploaded){
        if($this->currentDownloadItem instanceof Item){
            $strDownloading = str_pad(
                sprintf("\rDownloading[%d/%d][%s]%s %d%% %s/%s",
                    $this->downloadedItemsCount,
                    count($this->notDownloadedItems),
                    $this->currentDownloadItem->getUrl(),
                    $this->getDots(),
                    ($downloadSize == 0 ? 0 : intval(($downloaded / $downloadSize) * 100)),
                    self::formatBytes($downloaded),
                    self::formatBytes($downloadSize)
                )
                ,self::$terminalColumns, " "
            );

            echo $strDownloading;
        }

        //echo "Progress: " . intval(($downloaded / $downloadSize) * 100) . "%\r\n";
    }

    private static function formatBytes($bytes){
        if($bytes < 0)
            return "0 B";
        if ($bytes < 1000)
            return number_format($bytes) . " B";
        elseif ($bytes < 1000 * 1024)
            return number_format($bytes / 1024, 2) . " KB";
        elseif ($bytes < 1000 * 1048576)
            return number_format($bytes / 1048576, 2) . " MB";
        elseif ($bytes < 1000 * 1073741824)
            return number_format($bytes / 1073741824, 2) . " GB";
        elseif ($bytes < 1000 * 1099511627776)
            return number_format($bytes / 1099511627776, 2) . " TB";
    }
	
	private static function getTerminalSizeOnWindows() {
		$output = array();
		$size = array('width' => 0,'height' => 0);
		exec('mode',$output);
		
		foreach($output as $line){
			$matches = array();
			$w = preg_match('/^\s*columns\:?\s*(\d+)\s*$/i', $line, $matches);
			
			if($w){
				$size['width'] = intval($matches[1]);
			} 
			else{
				$h = preg_match('/^\s*lines\:?\s*(\d+)\s*$/i', $line, $matches);
				
				if($h){
					$size['height'] = intval($matches[1]);
				}
			}
			if($size['width'] AND $size['height']) {
			break;
			}
		}
	
		return $size;
	}

    public function shutdown(){
        echo "\r\nFinished!\r\n";
    }

    public function output($buffer){
        return $buffer;
    }

    public function tick(){
        $this->setCurrentSearch($this->currentSearchName);
    }
}