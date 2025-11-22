<?php
namespace OCA\Dashvideoplayerv2\Controller;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
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
     */
    public function index($fileId, $shareToken = NULL, $filePath = NULL)
    {
        try {
            $this->logger->info("PlayerController::index called with fileId: $fileId", ["app" => $this->appName]);

            /*if (empty($shareToken) && !$this->userSession->isLoggedIn()) {
                $redirectUrl = $this->urlGenerator->linkToRoute("core.login.showLoginForm", [
                    "redirect_url" => $this->request->getRequestUri()
                ]);
                return new RedirectResponse($redirectUrl);
            }*/

            if ($fileId) {
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
            } else {
                list($file, $error) = $this->getFileByToken($fileId, $shareToken);
                if (isset($error)) {
                     return new TemplateResponse($this->appName, "error", ["message" => $error]);
                }
                $relativePath = $file->getPath();
            }

            /* 
            Generate video's web url for the player to use as 'src' attr
            URL looks like this: http://localhost:8888/nextcloud/remote.php/webdav/directory/somevideofile.mpd
            */

            $baseUri = $this->urlGenerator->getWebroot() . '/remote.php/webdav';
            // Use IRequest to get protocol and host safely, handling proxies if configured in Nextcloud
            $protocol = $this->request->getServerProtocol();
            $host = $this->request->getServerHost();
            $videoUrl = "$protocol://$host$baseUri$relativePath";

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
            ];
        

            $response = new TemplateResponse($this->appName, "player", $params);

            $csp = new ContentSecurityPolicy();
            $csp->addAllowedScriptDomain("'unsafe-inline'");
            $csp->addAllowedConnectDomain('*');
            $csp->addAllowedImageDomain('*');
            $csp->addAllowedMediaDomain('*');        
            $csp->addAllowedFontDomain('*');         
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
       
}
