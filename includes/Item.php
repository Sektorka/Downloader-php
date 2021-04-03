<?php

namespace includes;

use includes\exceptions\InvalidArgumentException;
use JetBrains\PhpStorm\Pure;

class Item
{
    const STAT_IDLE = 1;
    const STAT_DOWNLOADING = 2;
    const STAT_DOWNLOADED = 3;
    const STAT_SKIPPED = 4;
    const STAT_FAILED = 5;

    private string $fileName, $url, $destination, $plugin;
    private int $fileSize, $status;

    public function __construct(string $fileName, string $url, string $destination, int $fileSize, IPlugin $plugin)
    {
        $this->fileName = $fileName;
        $this->url = $url;
        $this->destination = $destination;
        $this->fileSize = $fileSize;
        $this->plugin = $plugin;
        $this->status = self::STAT_IDLE;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getStatus(): int|string
    {
        return $this->status;
    }

    public function setStatus($status){
        if(!is_int($status) || $status < self::STAT_IDLE || $status > self::STAT_FAILED){
            throw new InvalidArgumentException(sprintf("Invalid status code: %d", $status));
        }

        $this->status = $status;
    }

    public function getStatusStr(): string
    {
        return match ($this->status) {
            self::STAT_IDLE => "Idle",
            self::STAT_DOWNLOADING => "Downloading",
            self::STAT_DOWNLOADED => "Downloaded",
            self::STAT_SKIPPED => "Skipped",
            self::STAT_FAILED => "Failed",
            default => "Undefined",
        };
    }

    #[Pure] public function __toString(): string
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