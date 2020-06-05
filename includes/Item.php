<?php

namespace includes;

use includes\exceptions\InvalidArgumentException;

class Item
{
    const STAT_IDLE = 1;
    const STAT_DOWNLOADING = 2;
    const STAT_DOWNLOADED = 3;
    const STAT_SKIPPED = 4;
    const STAT_FAILED = 5;

    const FILE_SIZE_NOT_REQ = -1;

    private $fileName, $url, $destination,
        $fileSize, $status, $plugin;

    public function __construct($fileName, $url, $destination, $fileSize, IPlugin $plugin)
    {
        $this->fileName = $fileName;
        $this->url = $url;
        $this->destination = $destination;
        $this->fileSize = $fileSize;
        $this->plugin = $plugin;
        $this->status = self::STAT_IDLE;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function getFileSize()
    {
        return $this->fileSize;
    }

    public function getPlugin()
    {
        return $this->plugin;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status){
        if(!is_int($status) || $status < self::STAT_IDLE || $status > self::STAT_FAILED){
            throw new InvalidArgumentException(sprintf("Invalid status code: %d", $status));
        }

        $this->status = $status;
    }

    public function getStatusStr(){
        switch($this->status){
            case self::STAT_IDLE:
                return "Idle";
            case self::STAT_DOWNLOADING:
                return "Downloading";
            case self::STAT_DOWNLOADED:
                return "Downloaded";
            case self::STAT_SKIPPED:
                return "Skipped";
            case self::STAT_FAILED:
                return "Failed";
            default:
                return "Undefined";
        }
    }

    public function __toString()
    {
        return sprintf("Item\r\n{\r\n  Filename: %s\r\n  Url: %s\r\n  Destination: %s\r\n  Filesize: %s\r\n  Status: %s\r\n}",
            $this->fileName,
            $this->url,
            $this->destination,
            $this->fileSize,
            $this->getStatusStr()
        );
    }


}