<?php
style("dashvideoplayer", "player");
script("dashvideoplayer", "dash.all.min");
?>

<div id="app-content">
    <video data-dashjs-player src="<?php p($_["videoUrl"]) ?>" controls="true"></video>
</div>