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
            poster="<?php p($coverUrl) ?>"
            data-manifest-url="<?php p($videoUrl) ?>"
            data-subtitles-url="<?php p($subtitlesUrl) ?>"
        ></video>
    </div>
</div>
