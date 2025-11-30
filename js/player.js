(function() {
    async function init() {
        if (window.dashVideoPlayerInitialized) {
            return;
        }
        window.dashVideoPlayerInitialized = true;

        const video = document.getElementById('video');
        if (!video) {
            console.error('DashVideoPlayer: Video element not found');
            return;
        }

        const manifestUri = video.getAttribute('data-stream-url');
        const subtitlesUrl = video.getAttribute('data-subtitles-url');
        const shareToken = video.getAttribute('data-share-token');
        const posterUrl = video.getAttribute('data-poster-url');

        // When using the UI, the player is made automatically by the UI object.
        const ui = video['ui'];
        const config = {
            'controlPanelElements': [
                'play_pause',
                'spacer',
                'time_and_duration',
                'mute',
                'volume',
                'captions',
                'quality',
                'fullscreen'
            ],
        };
        ui.configure(config);

        const controls = ui.getControls();
        const player = controls.getPlayer();

        // Add auth filter for public shares (WebDAV authentication)
        if (shareToken) {
            player.getNetworkingEngine().registerRequestFilter(function(type, request) {
                request.headers['Authorization'] = 'Basic ' + btoa(shareToken + ':');
            });
        }

        // Attach player and ui to the window for debugging
        window.player = player;
        window.ui = ui;

        // Listen for error events.
        player.addEventListener('error', onPlayerErrorEvent);
        controls.addEventListener('error', onUIErrorEvent);

        // Try to load a manifest.
        // This is an asynchronous process.
        try {
            await player.load(manifestUri);
            
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
        } catch (error) {
            onPlayerError(error);
        }
    }

    function onPlayerErrorEvent(errorEvent) {
        // Extract the shaka.util.Error object from the event.
        onPlayerError(errorEvent.detail);
    }

    function onPlayerError(error) {
        console.error('DashVideoPlayer: Error code', error.code, 'object', error);
    }

    function onUIErrorEvent(errorEvent) {
        onPlayerError(errorEvent.detail);
    }

    function initFailed(errorEvent) {
        console.error('DashVideoPlayer: Unable to load the UI library');
    }

    // Listen to the custom shaka-ui-loaded event
    document.addEventListener('shaka-ui-loaded', init);
    document.addEventListener('shaka-ui-load-failed', initFailed);

    // Check if UI is already loaded (in case script runs after event)
    document.addEventListener('DOMContentLoaded', () => {
        const video = document.getElementById('video');
        if (video && video['ui']) {
            init();
        }
    });

})();
