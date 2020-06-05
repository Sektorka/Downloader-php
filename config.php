<?php

spl_autoload_register(function ($class) {
    @include resource($class . CLASSES_EXT);
});

use \includes\Version;

define("DEBUG", false);

define("GUI_MODULE", "wxWidgets");
define("GUI_ENABLED", extension_loaded(GUI_MODULE));
define("CLASSES_EXT",".php");

define("APPLICATION_NAME","Downloader");
define("APPLICATION_VERSION", new Version(1,0,0,0));
define("AUTHOR_NAME", "Gyurász Krisztián");
define("AUTHOR_EMAIL", "krisztian@gyurasz.eu");

define("USER_AGENT", APPLICATION_NAME . " " . APPLICATION_VERSION);
define("COOKIES_FILE", "cookies.txt");

//proxy
define("PROXY_ENABLED", false);
define("PROXY_HOST", "192.168.20.65");
define("PROXY_PORT", 3128);
define("PROXY_AUTHENTICATION", true);
define("PROXY_USERNAME", "");
define("PROXY_PASSWORD", "");

