<?php

namespace includes;


use JetBrains\PhpStorm\Pure;

class Version
{
    private int $major, $minor, $mainteance, $build;

    public function __construct($major, $minor, $mainteance, $build)
    {
        $this->major = $major;
        $this->minor = $minor;
        $this->mainteance = $mainteance;
        $this->build = $build;
    }

    #[Pure] public function getVersion(): string
    {
        return sprintf("v%s.%s.%s.%s", $this->major, $this->minor, $this->mainteance, $this->build);
    }

    #[Pure] public function __toString(): string
    {
        return $this->getVersion();
    }
}