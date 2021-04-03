<?php

use \includes\Version;

const DEBUG = false;
const CLASSES_EXT = ".php";

const APPLICATION_NAME = "Downloader";
define("APPLICATION_VERSION", new Version(1,1,0,0));
const AUTHOR_NAME = "Gyurász Krisztián";
const AUTHOR_EMAIL = "krisztian@gyurasz.eu";

const USER_AGENT = APPLICATION_NAME . " " . APPLICATION_VERSION;
const COOKIES_FILE = "cookies.txt";

//proxy
const PROXY_ENABLED = false;
const PROXY_HOST = "192.168.20.65";
const PROXY_PORT = 3128;
const PROXY_AUTHENTICATION = true;
const PROXY_USERNAME = "";
const PROXY_PASSWORD = "";

