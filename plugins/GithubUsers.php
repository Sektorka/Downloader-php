<?php


namespace plugins;

use includes\Version;

class GithubUsers extends AbsGithub
{
    protected function getUsers(): array
    {
        return ['krakjoe', 'xiph', 'CacoFFF'];
    }

    protected function getUrl(): string
    {
        return "https://api.github.com/users/%s/repos?page=%d&per_page=100";
    }

    protected function getDir(): string
    {
        return "D:\\Downloads\\Github-users";
    }

    public function getPluginName(): string
    {
        return "GithubUsers";
    }

    public function getVersion(): Version
    {
        if($this->version == null){
            $this->version = new Version(1,0,0,0);
        }

        return $this->version;
    }

    public function getAuthorName(): string
    {
        return "Gyurász Krisztián";
    }

    public function hasSettings(): bool
    {
        return false;
    }

    public function showSettings()
    {

    }
}