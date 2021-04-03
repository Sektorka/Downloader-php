<?php


namespace plugins;


use DateTime;
use Exception;
use includes\exceptions\DownloaderException;
use includes\IPlugin;
use includes\Item;

abstract class AbsGithub extends IPlugin
{
    const DOWNLOAD = "https://codeload.github.com/%s/zip/refs/heads/master";

    protected abstract function getUrl(): string;
    protected abstract function getDir(): string;
    protected abstract function getUsers(): array;

    public function startSearch()
    {
        foreach($this->getUsers() as $user) {
            $page = 0;

            do {
                $url = sprintf($this->getUrl(), $user, $page++);

                if (($error = $this->getHttpContent($url)) == 0) {
                    if ($this->responseCode != 200) {
                        if (DEBUG) {
                            var_export($this->header);
                        }

                        break;
                    }

                    $this->decodeContent();
                    $json = json_decode($this->content);

                    if ($this->responseCode != 200 || count($json) == 0) {
                        if (DEBUG) {
                            var_export($this->header);
                        }

                        break;
                    }

                    foreach ($json as $index => $item) {
                        $fileName = str_replace("/", "-", $item->full_name) . "-" . $item->default_branch . ".zip";
                        $filePath = $this->getDir() . DIRECTORY_SEPARATOR . $fileName;
                        $durl = sprintf(self::DOWNLOAD, $item->full_name);
                        $fileSize = 0;//$this->getHttpContentLength($durl);

                        $exists = false;
                        if (file_exists($filePath) && is_file($filePath)) {
                            $fileModified = new DateTime();
                            $fileModified->setTimestamp(filemtime($filePath));

                            try {
                                $exists = (new DateTime($item->pushed_at) <= $fileModified);
                            } catch (Exception $e) {
                                echo($e->getMessage());
                            }
                        }

                        $item = new Item($fileName, $durl, $filePath, $fileSize, $this);
                        $item->setStatus($exists ? Item::STAT_SKIPPED : Item::STAT_IDLE);

                        $this->getManager()->addItem($item);
                        $this->getManager()->setCurrentSearch($item->getFileName());
                    }

                } else {
                    throw new DownloaderException(sprintf("Failed to get content from: %s", $url), $error);
                }
            } while (true);
        }
    }
}