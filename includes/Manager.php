<?php

namespace includes;

require_once "config.php";

use http\Header;
use \includes\exceptions\DownloaderException;
use \includes\exceptions\InvalidArgumentException;
use \DirectoryIterator;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionException;
use \SplFileInfo;


final class Manager{
    const PLUGINS_DIR = "plugins";

    private static bool $initialized;
    private static Manager $instance;
    private static int $terminalColumns;
    private array $plugins = array(), $items = array(), $notDownloadedItems = array(), $arguments;
    private string $usePlugin, $currentSearchName, $currentDownloadItem;
    private bool $downloading = false;
    private int $downloadedItemsCount = 0;
    private resource $file;
    private array|string $header;

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

		self::$terminalColumns = intval(self::getTerminalSizeOnWindows()["width"]);
        self::$initialized = true;
        self::$instance = new Manager($argv);
    }

    public static function getInstance(): Manager
    {
        if(!self::$initialized){
            throw new DownloaderException("First call initialize method!");
        }

        return self::$instance;
    }

    private function parseArguments(){
        if(count($this->arguments) > 1 && preg_match('/--plugin=(\w+)/i', $this->arguments[1], $plugin)){
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

        foreach(new DirectoryIterator(self::PLUGINS_DIR) as $file){
            if($file->isDot() || !($file instanceof SplFileInfo)) continue;

            require_once self::PLUGINS_DIR . DIRECTORY_SEPARATOR . $file->getFilename();

            $pluginClass = "\\plugins\\" . basename($file->getFilename(), ".php");
            try {
                $reflect = new ReflectionClass($pluginClass);

                if(!$reflect->isAbstract()) {
                    $this->plugins[] = new $pluginClass($this);
                }
            } catch (ReflectionException $e) {
                printException($e);
            }
        }


    }

    public function startSearch($autoStartDownload = false){
        foreach($this->plugins as $plugin){
            if($plugin instanceof IPlugin && $plugin == $this->usePlugin){
                echo sprintf("%s %s - Powered by: %s <%s>\nPlugin: %s \nAuthor: %s\nVersion: %s\n",
                    APPLICATION_NAME,
                    APPLICATION_VERSION,
                    AUTHOR_NAME,
                    AUTHOR_EMAIL,
                    $plugin->getPluginName(),
                    $plugin->getAuthorName(),
                    $plugin->getVersion()
                );

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
                $this->downloadedItemsCount++;
                $this->downloadItem($item);
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
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, "gotHttpHeader"));
        curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, array($this, "progressFunction"));
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, array($this, "writeFunction"));

        curl_exec($curl);
        $this->header = self::parseHeader($this->header);
        curl_close($curl);
        fclose($this->file);
    }

    public static final function parseHeader($header): array
    {
        if(is_string($header)) {
            return array_change_key_case((new Header)->parse($header));
        }

        return [];
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
            , self::$terminalColumns
        );

		echo $strSearching . ($fail ? "\n" : "");
		
		if($fail){
            sleep(2);
        }
    }

    private function getDots(): string
    {
        return match (time() % 4) {
            1 => ".  ",
            2 => ".. ",
            3 => "...",
            default => "   ",
        };
    }

    #[Pure] private function getTotalSizeOfNotDownloadedItems(): int
    {
        $totalSize = 0;

        foreach($this->notDownloadedItems as $item){
            if($item instanceof Item){
                $totalSize += $item->getFileSize();
            }
        }

        return $totalSize;
    }

    private function writeFunction($curl, $data): int
    {
        if(is_resource($this->file)){
            return fwrite($this->file, $data);
        }

        return 0;
    }

    private function gotHttpHeader($curl, $headerLine): int
    {
        $this->header .= $headerLine;
        return strlen($headerLine);
    }

    private function progressFunction($curl, $downloadSize, $downloaded, $uploadSize, $uploaded){
        if($this->currentDownloadItem instanceof Item){
            echo str_pad(
                sprintf("\rDownloading[%d/%d => %d%%][%s]%s %d%% %s/%s",
                    $this->downloadedItemsCount,
                    count($this->notDownloadedItems),
                    intval(($this->downloadedItemsCount / count($this->notDownloadedItems)) * 100),
                    $this->currentDownloadItem->getUrl(),
                    $this->getDots(),
                    ($downloadSize == 0 ? 100 : intval(($downloaded / $downloadSize) * 100)),
                    self::formatBytes($downloaded),
                    self::formatBytes($downloadSize == 0 ? $downloaded : $downloadSize)
                ),
                self::$terminalColumns
            );
        }

        //echo "Progress: " . intval(($downloaded / $downloadSize) * 100) . "%\r\n";
    }

    #[Pure] private static function formatBytes($bytes): string
    {
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
        else
            return number_format($bytes / 1125899906842624, 2) . " PB";
    }
	
	#[ArrayShape(['width' => "int", 'height' => "int"])] private static function getTerminalSizeOnWindows(): array
    {
		$output = array();
		$size = array('width' => 0,'height' => 0);
		exec('mode',$output);
		
		foreach($output as $line){
			$matches = array();
			$w = preg_match('/^\s*columns:?\s*(\d+)\s*$/i', $line, $matches);
			
			if($w){
				$size['width'] = intval($matches[1]);
			} 
			else{
				$h = preg_match('/^\s*lines:?\s*(\d+)\s*$/i', $line, $matches);
				
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

    public function tick(){
        $this->setCurrentSearch($this->currentSearchName);
    }
}