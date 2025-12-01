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
                $shareRoot = $node; // Save the share root for subtitle discovery
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
                $shareRoot = null; // No share root for logged-in users
                
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

            // Discover all subtitle files in the same directory
            $subtitlesList = $this->discoverSubtitles($file, $baseUri, $protocol, $host, $shareRoot ?? null);

            $params = [
                "fileId" => $fileId,  
                "videoUrl" => $videoUrl,
                "coverUrl" => $coverUrl,
                "subtitlesList" => $subtitlesList,
                "shareToken" => $shareToken
            ];

            // For public shares, use standalone HTML to avoid Nextcloud's auth redirect
            if ($shareToken) {
                return $this->createStandalonePlayerResponse($params);
            }
        
            // For logged-in users, also use standalone HTML for consistent UI
            return $this->createStandalonePlayerResponse($params);

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
     * Discover all subtitle files (.vtt) in the same directory as the video
     * Looks for patterns like: videoname.vtt, videoname_en.vtt, videoname_fr.vtt, etc.
     * 
     * @param $videoFile The video file node
     * @param $baseUri The base WebDAV URI
     * @param $protocol The protocol (http/https)
     * @param $host The server host
     * @param $shareRoot The share root node for public shares (null for logged-in users)
     */
    private function discoverSubtitles($videoFile, $baseUri, $protocol, $host, $shareRoot = null) {
        $subtitles = [];
        
        try {
            $parent = $videoFile->getParent();
            $videoName = $videoFile->getName();
            
            // Get base name without extension
            $baseName = preg_replace('/\.(mpd|m3u8)$/i', '', $videoName);
            
            // List all files in the directory
            $files = $parent->getDirectoryListing();
            
            // Language code to full name mapping
            $languageNames = [
                'en' => 'English',
                'fr' => 'Français',
                'es' => 'Español',
                'de' => 'Deutsch',
                'it' => 'Italiano',
                'pt' => 'Português',
                'ru' => 'Русский',
                'ja' => '日本語',
                'ko' => '한국어',
                'zh' => '中文',
                'ar' => 'العربية',
                'hi' => 'हिन्दी',
                'nl' => 'Nederlands',
                'pl' => 'Polski',
                'sv' => 'Svenska',
                'da' => 'Dansk',
                'fi' => 'Suomi',
                'no' => 'Norsk',
                'cs' => 'Čeština',
                'hu' => 'Magyar',
                'tr' => 'Türkçe',
                'el' => 'Ελληνικά',
                'he' => 'עברית',
                'th' => 'ไทย',
                'vi' => 'Tiếng Việt',
                'id' => 'Bahasa Indonesia',
                'ms' => 'Bahasa Melayu',
                'uk' => 'Українська',
                'ro' => 'Română',
                'bg' => 'Български',
                'hr' => 'Hrvatski',
                'sk' => 'Slovenčina',
                'sl' => 'Slovenščina',
                'et' => 'Eesti',
                'lv' => 'Latviešu',
                'lt' => 'Lietuvių',
            ];
            
            foreach ($files as $file) {
                if ($file->getType() !== 'file') continue;
                
                $fileName = $file->getName();
                
                // Check if it's a VTT file matching our video
                if (preg_match('/^' . preg_quote($baseName, '/') . '(?:_([a-z]{2}(?:-[A-Z]{2})?))?\.vtt$/i', $fileName, $matches)) {
                    $langCode = isset($matches[1]) ? strtolower($matches[1]) : 'und'; // 'und' for undefined
                    $shortCode = explode('-', $langCode)[0]; // Get just 'fr' from 'fr-ca'
                    
                    // Get language name
                    $langName = $languageNames[$shortCode] ?? ucfirst($langCode);
                    if (strpos($langCode, '-') !== false) {
                        // Has region code like fr-CA
                        $parts = explode('-', $langCode);
                        $langName = ($languageNames[$parts[0]] ?? ucfirst($parts[0])) . ' (' . strtoupper($parts[1]) . ')';
                    }
                    
                    // Build URL for this subtitle file
                    $subtitlePath = $parent->getPath() . '/' . $fileName;
                    
                    // Get relative path from user folder or share root
                    if ($shareRoot !== null) {
                        // For public shares, calculate path relative to share root
                        if ($shareRoot instanceof \OCP\Files\Folder) {
                            $relativePath = $shareRoot->getRelativePath($file->getPath());
                        } else {
                            // Share is a single file's parent folder case
                            $relativePath = '/' . $fileName;
                        }
                    } elseif ($this->userSession->isLoggedIn()) {
                        $uid = $this->userSession->getUser()->getUID();
                        $userFolder = $this->root->getUserFolder($uid);
                        $relativePath = $userFolder->getRelativePath($subtitlePath);
                    } else {
                        // Fallback
                        $relativePath = '/' . $fileName;
                    }
                    
                    $encodedPath = str_replace('%2F', '/', rawurlencode($relativePath));
                    if (strpos($encodedPath, '/') !== 0) {
                        $encodedPath = '/' . $encodedPath;
                    }
                    
                    $subtitleUrl = "$protocol://$host$baseUri$encodedPath";
                    
                    $subtitles[] = [
                        'url' => $subtitleUrl,
                        'lang' => $langCode,
                        'label' => $langName
                    ];
                }
            }
            
            // Sort by language code
            usort($subtitles, function($a, $b) {
                return strcmp($a['lang'], $b['lang']);
            });
            
        } catch (\Exception $e) {
            $this->logger->warning('Failed to discover subtitles: ' . $e->getMessage(), ['app' => $this->appName]);
        }
        
        return $subtitles;
    }

    /**
     * Create a standalone HTML response for public share player
     * This bypasses Nextcloud's template system to avoid auth redirects
     */
    private function createStandalonePlayerResponse($params) {
        $videoUrl = $params['videoUrl'];
        $coverUrl = $params['coverUrl'];
        $subtitlesList = json_encode($params['subtitlesList']);
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
                data-subtitles-list='{$subtitlesList}'
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
