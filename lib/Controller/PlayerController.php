<?php
namespace OCA\Dashvideoplayerv2\Controller;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Controller;
use OCP\Constants;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Files\NotFoundException;

use OCA\Dashvideoplayerv2\AppConfig;


class PlayerController extends Controller
{

    private $userSession;
    private $root;
    private $urlGenerator;    
    private $logger;    
    /**
     * Session
     *
     * @var ISession
     */
    private $session;
    /**
     * Share manager
     *
     * @var IManager
     */
    private $shareManager;
    private $config;
    protected $appName;

    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IRootFolder $root - root folder
     * @param IUserSession $userSession - current user session
     * @param IURLGenerator $urlGenerator - url generator service     
     * @param ILogger $logger - logger
     * @param AppConfig $config - app config
     * @param IManager $shareManager - share manager
     * @param ISession $session - session
     */
    public function __construct(
        $AppName,
        IRequest $request,
        IRootFolder $root,
        IUserSession $userSession,
        IURLGenerator $urlGenerator,        
        LoggerInterface $logger,
        AppConfig $config,
        IManager $shareManager,
        ISession $session
    ) {
        parent::__construct($AppName, $request);

        $this->appName = $AppName;
        $this->userSession = $userSession;
        $this->root = $root;
        $this->urlGenerator = $urlGenerator;      
        $this->logger = $logger;
        $this->config = $config;
        $this->shareManager = $shareManager;
        $this->session = $session;
        
        $this->logger->info("PlayerController initialized for app: " . $AppName, ["app" => $AppName]);
    }

    /**
     * This comment is very important, CSRF fails without it
     *
     * @param integer $fileId - file identifier
     *
     * @return TemplateResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function index($fileId, $shareToken = NULL, $filePath = NULL)
    {
        try {
            $baseUri = '';
            $relativePath = '';

            if ($shareToken) {
                list($file, $error) = $this->getFileByToken($fileId, $shareToken);
                if (isset($error)) {
                    return new TemplateResponse($this->appName, "error", ["message" => $error]);
                }
                
                // Calculate relative path for public share
                list($node, $err, $share) = $this->getNodeByToken($shareToken);
                if ($node instanceof \OCP\Files\Folder) {
                    $relativePath = $node->getRelativePath($file->getPath());
                } else {
                    $relativePath = $file->getName();
                }
                
                // Use public WebDAV endpoint for streaming
                $baseUri = $this->urlGenerator->getWebroot() . '/public.php/webdav';
            } elseif ($fileId) {
                list($file, $error) = $this->getFile($fileId);
                if (isset($error)) {
                    $this->logger->error("Load: " . $fileId . " " . $error, array("app" => $this->appName));
                    return new TemplateResponse($this->appName, "error", ["message" => $error]);
                }
                
                $uid = $this->userSession->getUser()->getUID();
                $baseFolder = $this->root->getUserFolder($uid);
                
                // Robust path calculation
                $filePath = $file->getPath();
                $userFolderPath = $baseFolder->getPath();
                
                if (strpos($filePath, $userFolderPath) === 0) {
                    // File is inside user folder
                    $relativePath = substr($filePath, strlen($userFolderPath));
                } else {
                    // File might be shared or external, try getRelativePath but catch errors
                    try {
                        $relativePath = $baseFolder->getRelativePath($filePath);
                    } catch (\Exception $e) {
                        // Fallback: Use the file's internal path if possible, or just the name
                        $this->logger->warning("Could not get relative path for $filePath: " . $e->getMessage(), ["app" => $this->appName]);
                        $relativePath = '/' . $file->getName(); // Desperate fallback
                    }
                }
                $baseUri = $this->urlGenerator->getWebroot() . '/remote.php/webdav';
            } else {
                // Fallback for legacy calls?
                list($file, $error) = $this->getFileByToken($fileId, $shareToken);
                if (isset($error)) {
                     return new TemplateResponse($this->appName, "error", ["message" => $error]);
                }
                $relativePath = $file->getPath();
                $baseUri = $this->urlGenerator->getWebroot() . '/remote.php/webdav';
            }

            /* 
            Generate video's web url for the player to use as 'src' attr
            */

            // Use IRequest to get protocol and host safely, handling proxies if configured in Nextcloud
            $protocol = $this->request->getServerProtocol();
            $host = $this->request->getServerHost();
            
            // Encode the path segments to ensure URL safety (handling spaces, etc.)
            // We use rawurlencode to encode spaces as %20 (not +), and then restore the slashes
            $encodedRelativePath = str_replace('%2F', '/', rawurlencode($relativePath));
            
            // Ensure leading slash
            if (strpos($encodedRelativePath, '/') !== 0) {
                $encodedRelativePath = '/' . $encodedRelativePath;
            }
            
            $videoUrl = "$protocol://$host$baseUri$encodedRelativePath";

            $coverUrl = "";
            if (strpos($videoUrl, '.mpd') !== false)
                $coverUrl = str_replace(".mpd", ".jpg", $videoUrl);
            if (strpos($videoUrl, '.m3u8') !== false)
                $coverUrl = str_replace(".m3u8",".jpg",$videoUrl);

            $subtitlesUrl = "";
            if (strpos($videoUrl, '.mpd') !== false)
                $subtitlesUrl = str_replace(".mpd", ".vtt", $videoUrl);
            if (strpos($videoUrl, '.m3u8') !== false)
                $subtitlesUrl = str_replace(".m3u8",".vtt",$videoUrl);

            $params = [
                "fileId" => $fileId,  
                "videoUrl" => $videoUrl,
                "coverUrl" => $coverUrl,
                "subtitlesUrl" => $subtitlesUrl,
                "shareToken" => $shareToken
            ];

            // For public shares, use standalone HTML to avoid Nextcloud's auth redirect
            if ($shareToken) {
                return $this->createStandalonePlayerResponse($params);
            }
        
            $response = new TemplateResponse($this->appName, "player", $params);

            $csp = new ContentSecurityPolicy();
            $csp->addAllowedScriptDomain("'unsafe-inline'");
            $csp->addAllowedScriptDomain('blob:');
            $csp->addAllowedScriptDomain('data:');
            $csp->addAllowedConnectDomain('*');
            $csp->addAllowedConnectDomain('blob:');
            $csp->addAllowedConnectDomain('data:');
            $csp->addAllowedImageDomain('*');
            $csp->addAllowedImageDomain('blob:');
            $csp->addAllowedImageDomain('data:');
            $csp->addAllowedMediaDomain('*');
            $csp->addAllowedMediaDomain('blob:');
            $csp->addAllowedMediaDomain('data:');
            $csp->addAllowedFontDomain('*');
            $csp->addAllowedFontDomain('blob:');
            $csp->addAllowedFontDomain('data:');
            $response->setContentSecurityPolicy($csp);

            return $response;

        } catch (\Throwable $e) {
            $this->logger->error("PlayerController Index Error: " . $e->getMessage() . "\n" . $e->getTraceAsString(), ["app" => $this->appName]);
            return new TemplateResponse($this->appName, "error", ["message" => "Internal Server Error: " . $e->getMessage()]);
        }
    }

    /**
     * @NoAdminRequired
     */
    private function getFile($fileId)
    {
        if (empty($fileId)) {
            return [null, "FileId is empty"];
        }

        $files = $this->root->getById($fileId);
        if (empty($files)) {
            return [null, "File not found"];
        }
        $file = $files[0];

        if (!$file->isReadable()) {
            return [null, "You do not have enough permissions to view the file"];
        }
        return [$file, null];
    }

    /**
     * Getting file by token
     *
     * @param integer $fileId - file identifier
     * @param string $shareToken - access token
     *
     * @return array
     */
    private function getFileByToken($fileId, $shareToken)
    {
        list($node, $error, $share) = $this->getNodeByToken($shareToken);

        if (isset($error)) {
            return [NULL, $error, NULL];
        }

        if ($node instanceof \OCP\Files\Folder) {
            try {
                $files = $node->getById($fileId);
            } catch (\Exception $e) {
                $this->logger->error("getFileByToken: $fileId " . $e->getMessage(), array("app" => $this->appName));
                return [NULL, "Invalid request", NULL];
            }

            if (empty($files)) {
                $this->logger->info("Files not found: $fileId", array("app" => $this->appName));
                return [NULL, "File not found", NULL];
            }
            $file = $files[0];
        } else {
            $file = $node;
        }

        return [$file, NULL, $share];
    }

    /**
     * Getting file by token
     *
     * @param string $shareToken - access token
     *
     * @return array
     */
    private function getNodeByToken($shareToken)
    {
        list($share, $error) = $this->getShare($shareToken);

        if (isset($error)) {
            return [NULL, $error, NULL];
        }

        if (($share->getPermissions() & Constants::PERMISSION_READ) === 0) {
            return [NULL, "You do not have enough permissions to view the file", NULL];
        }

        try {
            $node = $share->getNode();
        } catch (NotFoundException $e) {
            $this->logger->error("getFileByToken error: " . $e->getMessage(), array("app" => $this->appName));
            return [NULL, "File not found", NULL];
        }

        return [$node, NULL, $share];
    }

    /**
     * Getting share by token
     *
     * @param string $shareToken - access token
     *
     * @return array
     */
    private function getShare($shareToken)
    {
        if (empty($shareToken)) {
            return [NULL, "FileId is empty"];
        }

        $share = null;
        try {
            $share = $this->shareManager->getShareByToken($shareToken);
        } catch (ShareNotFound $e) {
            $this->logger->error("getShare error: " . $e->getMessage(), array("app" => $this->appName));
            $share = NULL;
        }

        if ($share === NULL || $share === false) {
            return [NULL, "You do not have enough permissions to view the file"];
        }

        if (
            $share->getPassword()
            && (!$this->session->exists("public_link_authenticated")
            || $this->session->get("public_link_authenticated") !== (string) $share->getId())
        ) {
            return [NULL, "You do not have enough permissions to view the file"];
        }

        return [$share, NULL];
    }

    /**
     * Create a standalone HTML response for public share player
     * This bypasses Nextcloud's template system to avoid auth redirects
     */
    private function createStandalonePlayerResponse($params) {
        $videoUrl = $params['videoUrl'];
        $coverUrl = $params['coverUrl'];
        $subtitlesUrl = $params['subtitlesUrl'];
        $shareToken = $params['shareToken'];
        
        // Get the app's web path for loading assets
        $appWebPath = $this->urlGenerator->linkTo($this->appName, '');
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Player</title>
    <link rel="stylesheet" href="{$appWebPath}css/controls.css">
    <link rel="stylesheet" href="{$appWebPath}css/player.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background-color: black;
            overflow: hidden;
        }
        #app-content {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    <div id="app-content">
        <div data-shaka-player-container style="position: absolute; top: 0; bottom: 0; left: 0; width: 100%; height: 100%; border: 0; background-color: black;">
            <video data-shaka-player autoplay 
                style="position: absolute; top: 0; bottom: 0; left: 0; width: 100%; height: 100%; border: 0; background-color: black;" 
                id="video" 
                data-poster-url="{$coverUrl}"
                data-stream-url="{$videoUrl}"
                data-subtitles-url="{$subtitlesUrl}"
                data-share-token="{$shareToken}"
            ></video>
        </div>
    </div>
    <script src="{$appWebPath}js/mux.js"></script>
    <script src="{$appWebPath}js/shaka-player.ui.js"></script>
    <script src="{$appWebPath}js/player.js"></script>
</body>
</html>
HTML;

        $response = new Response();
        $response->setStatus(200);
        $response->addHeader('Content-Type', 'text/html; charset=utf-8');
        
        // Set CSP headers manually
        $cspValue = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data:; style-src 'self' 'unsafe-inline'; img-src * blob: data:; media-src * blob: data:; connect-src * blob: data:; font-src * blob: data:;";
        $response->addHeader('Content-Security-Policy', $cspValue);
        
        // Custom response class to output HTML
        return new class($html) extends Response {
            private $html;
            public function __construct($html) {
                parent::__construct();
                $this->html = $html;
                $this->setStatus(200);
                $this->addHeader('Content-Type', 'text/html; charset=utf-8');
                $cspValue = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data:; style-src 'self' 'unsafe-inline'; img-src * blob: data:; media-src * blob: data:; connect-src * blob: data:; font-src * blob: data:;";
                $this->addHeader('Content-Security-Policy', $cspValue);
            }
            public function render(): string {
                return $this->html;
            }
        };
    }
       
}
