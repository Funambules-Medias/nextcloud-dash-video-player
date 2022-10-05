<?php
style("dashvideoplayer", "player");
//script("dashvideoplayer", "dash.all.min");
script("dashvideoplayer", "shaka-player.min");

?>

<div id="app-content">
    <!--<video data-dashjs-player src="<?php p($_["videoUrl"]) ?>" controls="true"></video>-->
    <video id="video" crossorigin="anonymous" poster="<?php p($coverUrl)?>" controls autoplay>
        Your browser does not support HTML5 video.
    </video>
</div>

<script type="text/javascript" nonce="<?php p(base64_encode($_["requesttoken"])) ?>" defer>
    window.addEventListener('DOMContentLoaded', function() {

        // 'https://storage.googleapis.com/shaka-demo-assets/angel-one/dash.mpd';
        const manifestUri = '<?php p($videoUrl) ?>'

        function initApp() {
            // Install built-in polyfills to patch browser incompatibilities.
            shaka.polyfill.installAll();

            // Check to see if the browser supports the basic APIs Shaka needs.
            if (shaka.Player.isBrowserSupported()) {
                // Everything looks good!
                initPlayer();
            } else {
                // This browser does not have the minimum set of APIs we need.
                console.error('Browser not supported!');
            }
        }

        async function initPlayer() {
            // Create a Player instance.
            const video = document.getElementById('video');
            const player = new shaka.Player(video);
            player.setTextTrackVisibility(true);


            // Attach player to the window to make it easy to access in the JS console.
            window.player = player;

            // Listen for error events.
            player.addEventListener('error', onErrorEvent);

            // Try to load a manifest.
            // This is an asynchronous process.
            try {
                await player.load(manifestUri);
                // This runs if the asynchronous load is successful.
                console.log('The video has now been loaded!');
            } catch (e) {
                // onError is executed if the asynchronous load fails.
                onError(e);
            }
        }

        function onErrorEvent(event) {
            // Extract the shaka.util.Error object from the event.
            onError(event.detail);
        }

        function onError(error) {
            // Log the error.
            console.error('Error code', error.code, 'object', error);
        }

        initApp();

    });
</script>