<?php
style("dashvideoplayer", "controls");
script("dashvideoplayer", "mux");
script("dashvideoplayer", "shaka-player.ui");
?>

<div id="app-content">
    <div data-shaka-player-container style="position: absolute; top: 0; bottom: 0; left: 0; width: 100%; height: 100%; border: 0; background-color: black;">
        <video data-shaka-player style="position: absolute; top: 0; bottom: 0; left: 0; width: 100%; height: 100%; border: 0; background-color: black;" id="video" poster="<?php p($coverUrl) ?>"></video>
    </div>
</div>

<script type="text/javascript" nonce="<?php p(base64_encode($_["requesttoken"])) ?>" defer>
    window.addEventListener('DOMContentLoaded', function() {

        const manifestUri = '<?php p($videoUrl) ?>'
        async function init() {
            // Install built-in polyfills to patch browser incompatibilities.
            shaka.polyfill.installAll();

            // Check to see if the browser supports the basic APIs Shaka needs.
            if (shaka.Player.isBrowserSupported()) {
                // Everything looks good!
                console.log("SHAKA WILL START NOW");
                initPlayer();
            } else {
                // This browser does not have the minimum set of APIs we need.
                console.error('Browser not supported!');
            }
            return;
            
            const video = document.getElementById('video');
            const ui = video['ui'];
            const config = {
                'controlPanelElements': [
                    'time_and_duration',
                    'play_pause',
                    'mute',
                    'volume',
                    'airplay',
                    'cast',
                    'fullscreen',
                    'captions',
                    'quality'
                ],
            };
            ui.configure(config);

            const controls = ui.getControls();
            const player = controls.getPlayer();

            // Attach player and ui to the window to make it easy to access in the JS console.
            window.player = player;
            window.ui = ui;

            // Listen for error events.
            player.addEventListener('error', onPlayerErrorEvent);
            controls.addEventListener('error', onUIErrorEvent);

            // Try to load a manifest.        
            try {
                await player.load(manifestUri);
                player.addTextTrackAsync(<?php p($subtitlesUrl) ?>, 'fr-CA', 'subtitles');
                player.setTextTrackVisibility(true);
                await player.configure({
                    preferredTextLanguage: 'fr-CA',
                });
                console.log(">>>>>>>>>>> SHAKA STARTED");
            } catch (error) {
                console.log(">>>>>>>>>>> SHAKA ERROR: ", error);
                onPlayerError(error);
            }
        }

        function onPlayerErrorEvent(errorEvent) {
            // Extract the shaka.util.Error object from the event.
            onPlayerError(event.detail);
        }

        function onPlayerError(error) {
            // Handle player error
            console.error('Error code', error.code, 'object', error);
        }

        function onUIErrorEvent(errorEvent) {
            // Extract the shaka.util.Error object from the event.
            onPlayerError(event.detail);
        }

        function initFailed(errorEvent) {
            // Handle the failure to load; errorEvent.detail.reasonCode has a
            // shaka.ui.FailReasonCode describing why.
            console.error('Unable to load the UI library!');
        }

        // Listen to the custom shaka-ui-loaded event, to wait until the UI is loaded.
        document.addEventListener('shaka-ui-loaded', init);
        // Listen to the custom shaka-ui-load-failed event, in case Shaka Player fails
        // to load (e.g. due to lack of browser support).
        document.addEventListener('shaka-ui-load-failed', initFailed);

    });
</script>