<?php


namespace plugins;

use includes\Version;

class GithubStarred extends AbsGithub
{
    protected function getUsers(): array
    {
        return ['Sektorka'];
    }

    protected function getUrl(): string
    {
        return "https://api.github.com/users/%s/starred?page=%d&per_page=100";
    }

    protected function getDir(): string
    {
        return "D:\\Downloads\\Github-starred";
    }

    public function getPluginName(): string
    {
        return "GithubStarred";
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