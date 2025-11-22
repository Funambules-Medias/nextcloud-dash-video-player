(function() {
    console.log('DASHVIDEOPLAYERV2: player.js loaded');

    async function init() {
        if (window.dashVideoPlayerInitialized) {
            console.log('DASHVIDEOPLAYERV2: Already initialized, skipping');
            return;
        }
        window.dashVideoPlayerInitialized = true;

        const video = document.getElementById('video');
        if (!video) {
            console.error('DASHVIDEOPLAYERV2: Video element not found');
            return;
        }

        const manifestUri = video.getAttribute('data-manifest-url');
        const subtitlesUrl = video.getAttribute('data-subtitles-url');

        console.log('DASHVIDEOPLAYERV2: Initializing player with manifest:', manifestUri);

        // When using the UI, the player is made automatically by the UI object.
        const ui = video['ui'];
        const config = {
            'controlPanelElements': [
                'play_pause',
                'spacer',
                'time_and_duration',
                'mute',
                'volume',
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
        // This is an asynchronous process.
        try {
            await player.load(manifestUri);
            console.log('DASHVIDEOPLAYERV2: Manifest loaded successfully');
            
            if (subtitlesUrl) {
                player.addTextTrackAsync(subtitlesUrl, 'fr-CA', 'subtitles');
                player.setTextTrackVisibility(true);
            }
            
            await player.configure({
                preferredTextLanguage: 'fr-CA',
                streaming: {
                    bufferingGoal: 120,
                    rebufferingGoal: 0.5,
                    bufferBehind: 5,
                    lowLatencyMode: true,
                },
                manifest: {
                    dash: {
                        ignoreMinBufferTime: true
                    }
                },
                abr: {
                    defaultBandwidthEstimate: 50000,
                    switchInterval: 1
                }
            });
            console.log('DASHVIDEOPLAYERV2: Player configuration:', player.getConfiguration())

            // This runs if the asynchronous load is successful.
            console.log('DASHVIDEOPLAYERV2: The video has now been loaded!');
        } catch (error) {
            onPlayerError(error);
        }
    }

    function onPlayerErrorEvent(errorEvent) {
        // Extract the shaka.util.Error object from the event.
        onPlayerError(errorEvent.detail);
    }

    function onPlayerError(error) {
        // Handle player error
        console.error('DASHVIDEOPLAYERV2: Error code', error.code, 'object', error);
        if (error.data) {
            console.error('DASHVIDEOPLAYERV2: Error data', error.data);
        }
    }

    function onUIErrorEvent(errorEvent) {
        // Extract the shaka.util.Error object from the event.
        onPlayerError(errorEvent.detail);
    }

    function initFailed(errorEvent) {
        // Handle the failure to load; errorEvent.detail.reasonCode has a
        // shaka.ui.FailReasonCode describing why.
        console.error('DASHVIDEOPLAYERV2: Unable to load the UI library!');
    }

    // Listen to the custom shaka-ui-loaded event, to wait until the UI is loaded.
    document.addEventListener('shaka-ui-loaded', init);
    // Listen to the custom shaka-ui-load-failed event, in case Shaka Player fails
    // to load (e.g. due to lack of browser support).
    document.addEventListener('shaka-ui-load-failed', initFailed);

    // Check if UI is already loaded (in case script runs after event)
    document.addEventListener('DOMContentLoaded', () => {
        const video = document.getElementById('video');
        if (video && video['ui']) {
            console.log('DASHVIDEOPLAYERV2: UI already loaded, initializing immediately');
            init();
        } else {
            console.log('DASHVIDEOPLAYERV2: Waiting for shaka-ui-loaded event');
        }
    });

})();
