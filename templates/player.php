<?php
style("dashvideoplayerv2", "controls");
style("dashvideoplayerv2", "player");
script("dashvideoplayerv2", "mux");
script("dashvideoplayerv2", "shaka-player.ui");
script("dashvideoplayerv2", "player");
?>

<div id="app-content">
    <div data-shaka-player-container style="position: absolute; top: 0; bottom: 0; left: 0; width: 100%; height: 100%; border: 0; background-color: black;">
        <video data-shaka-player autoplay 
            style="position: absolute; top: 0; bottom: 0; left: 0; width: 100%; height: 100%; border: 0; background-color: black;" 
            id="video" 
            data-poster-url="<?php p($coverUrl) ?>"
            data-stream-url="<?php p($videoUrl) ?>"
            data-subtitles-list='<?php echo json_encode($_["subtitlesList"]); ?>'
            data-share-token="<?php p($_['shareToken']) ?>"
        ></video>
    </div>
</div>
