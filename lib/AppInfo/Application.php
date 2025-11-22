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
            return $c->query("ServerContainer")->get(\OCP\Files\IRootFolder::class);
        });

        $context->registerService("UserSession", function ($c) {
            return $c->query("ServerContainer")->get(\OCP\IUserSession::class);
        });

        $context->registerService("Logger", function ($c) {
            return $c->query("ServerContainer")->get(\Psr\Log\LoggerInterface::class);
        });

        $context->registerService("OCP\IRequest", function ($c) {
            return $c->query("ServerContainer")->get(\OCP\IRequest::class);
        });

        $context->registerService(PlayerController::class, function ($c) {
            return new PlayerController(
                $c->query("AppName"),
                $c->query("OCP\IRequest"),
                $c->query("RootStorage"),
                $c->query("UserSession"),
                $c->query("ServerContainer")->get(\OCP\IURLGenerator::class),
                $c->query("Logger"),
                $this->appConfig,
                $c->query("ServerContainer")->get(\OCP\Share\IManager::class),
                $c->query("ServerContainer")->get(\OCP\ISession::class)
            );
        });

        $context->registerService(ViewerController::class, function ($c) {
            $uid = $c->query("UserSession")->isLoggedIn() ? $c->query("UserSession")->getUser()->getUID() : null;
            return new ViewerController(
                $c->query("AppName"),
                $c->query("OCP\IRequest"),
                $c->query("RootStorage"),
                $c->query("UserSession"),
                $c->query("ServerContainer")->get(\OCP\IURLGenerator::class),
                $c->query("Logger"),
                $this->appConfig,
                $c->query("ServerContainer")->get(\OCP\Share\IManager::class),
                $c->query("ServerContainer")->get(\OCP\ISession::class),
                $uid
            );
        });

        $context->registerService(SettingsController::class, function ($c) {
            return new SettingsController(
                $c->query("AppName"),
                $c->query("OCP\IRequest"),
                $this->appConfig
            );
        });
    }

    public function boot(IBootContext $context): void
    {
        $appName = "dashvideoplayerv2";

        // Use addInitScript for better timing with the new Files app
        Util::addScript($appName, "main");
        Util::addStyle($appName, "main");
    }
}
