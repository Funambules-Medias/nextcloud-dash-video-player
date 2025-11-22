<?php

namespace OCA\Dashvideoplayerv2;

class AppConfig
{
    private $appName;

    public function __construct($AppName)
    {
        $this->appName = $AppName;
    }


    public function GetAppName()
    {
        return $this->appName;
    }

    /**
     * Additional data about formats
     *
     * @var array
     */
    public $formats = [
        "mpd" => ["mime" => "application/mpd", "type" => "video"],
        "m3u8" => ["mime" => "application/m3u8", "type" => "video"]
    ];
}
