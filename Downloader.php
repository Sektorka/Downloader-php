<?php

chdir(dirname($argv[0]));

function resource($file)
{
    return (defined('EMBEDED') ? 'res:///PHP/'.md5($file) : $file);
}

require_once resource("config.php");

use \includes\Manager;

try {
    Manager::initialize($argv);
    $manager = Manager::getInstance();
    $manager->startSearch(true);

    //ob_start(array($manager, "output"));
    /*foreach ($manager->getPlugins() as $plugins) {
            if ($plugins instanceof IPlugin) {
                print "plug: " . $plugins->getPluginName() . "\n";
            }
        }*/

}
catch(\includes\exceptions\DownloaderException $e){
    exit(sprintf("Error: %s\r\nCode: %d\r\nFile: %s\r\nLine: %d",
        $e->getMessage(),
        $e->getCode(),
        $e->getFile(),
        $e->getLine()
    ));
}