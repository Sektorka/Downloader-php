<?php

namespace includes;


class Version
{
    private $major, $minor, $mainteance, $build;

    public function __construct($major, $minor, $mainteance, $build)
    {
        $this->major = $major;
        $this->minor = $minor;
        $this->mainteance = $mainteance;
        $this->build = $build;
    }

    public function getMajor()
    {
        return $this->major;
    }

    public function getMinor()
    {
        return $this->minor;
    }

    public function getMainteance()
    {
        return $this->mainteance;
    }

    public function getBuild()
    {
        return $this->build;
    }

    public function getVersion(){
        return sprintf("v%s.%s.%s.%s", $this->major, $this->minor, $this->mainteance, $this->build);
    }

    public function __toString(){
        return $this->getVersion();
    }
}