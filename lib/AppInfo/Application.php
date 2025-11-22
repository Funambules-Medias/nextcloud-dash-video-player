<?php

namespace OCA\Dashvideoplayerv2\AppInfo;

use OCP\AppFramework\App;
use OCP\Util;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

use OCA\Dashvideoplayerv2\AppConfig;
use OCA\Dashvideoplayerv2\Controller\PlayerController;
use OCA\Dashvideoplayerv2\Controller\ViewerController;
use OCA\Dashvideoplayerv2\Controller\SettingsController;

class Application extends App implements IBootstrap
{

    public $appConfig;

    public function __construct(array $urlParams = [])
    {
        $appName = "dashvideoplayerv2";
        parent::__construct($appName, $urlParams);
    }

    public function register(IRegistrationContext $context): void
    {
        $appName = "dashvideoplayerv2";
        $this->appConfig = new AppConfig($appName);

        $context->registerService("RootStorage", function ($c) {
            return $c->query("ServerContainer")->getRootFolder();
        });

        $context->registerService("UserSession", function ($c) {
            return $c->query("ServerContainer")->getUserSession();
        });

        $context->registerService("Logger", function ($c) {
            return $c->query("ServerContainer")->getLogger();
        });

        $context->registerService("PlayerController", function ($c) {
            return new PlayerController(
                $c->query("AppName"),
                $c->query("Request"),
                $c->query("RootStorage"),
                $c->query("UserSession"),
                $c->query("ServerContainer")->getURLGenerator(),
                $c->query("Logger"),
                $this->appConfig,
                $c->query("ServerContainer")->getShareManager(),
                $c->query("ServerContainer")->getSession()
            );
        });

        $context->registerService("ViewerController", function ($c) {
            $uid = $c->query("UserSession")->isLoggedIn() ? $c->query("UserSession")->getUser()->getUID() : null;
            return new ViewerController(
                $c->query("AppName"),
                $c->query("Request"),
                $c->query("RootStorage"),
                $c->query("UserSession"),
                $c->query("ServerContainer")->getURLGenerator(),
                $c->query("Logger"),
                $this->appConfig,
                $c->query("ServerContainer")->getShareManager(),
                $c->query("ServerContainer")->getSession(),
                $uid
            );
        });

        $context->registerService("SettingsController", function ($c) {
            return new SettingsController(
                $c->query("AppName"),
                $c->query("Request"),
                $this->appConfig
            );
        });
    }

    public function boot(IBootContext $context): void
    {
        $appName = "dashvideoplayerv2";

        // Load legacy Files app scripts to restore OCA.Files.fileActions and OCA.Files.FileList
        // Using addInitScript to ensure they load at the correct time for NC28+
        Util::addScript('files', 'fileactions');
        Util::addScript('files', 'filelist');

        // Use addInitScript for better timing with the new Files app
        Util::addScript($appName, "main");
        Util::addStyle($appName, "main");
    }
}
