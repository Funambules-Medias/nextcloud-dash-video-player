<?php
namespace OCA\Dashvideoplayerv2\Controller;

// Core
use OCP\AppFramework\Controller;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;

// App
use OCA\Dashvideoplayerv2\AppConfig;

class SettingsController extends Controller
{
    private $config;
    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object     
     * @param OCA\Dashvideoplayerv2\AppConfig $config - application configuration
     */
    public function __construct($AppName,
                                IRequest $request,                                
                                AppConfig $config
                                )
    {
        parent::__construct($AppName, $request);    
        $this->config = $config;
    }

    

    /**
     * Get supported formats
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getsettings()
    {
         $data = array();
         $data['formats'] = $this->config->formats;
         $data['settings'] = array();         
         return new JSONResponse($data);
    }

}
