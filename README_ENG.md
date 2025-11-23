# Dash Video Player v2 (PFE)

A Nextcloud application integrating **Shaka Player** for native playback of adaptive video streams (MPEG-DASH and HLS) directly from the Files interface.

## 🌟 Features

* **Adaptive Playback:** Supports `.mpd` (DASH) and `.m3u8` (HLS) manifests.
* **Native Integration:** Opens directly upon clicking a file in the Files app.
* **Public Support:** Enables video playback via Nextcloud public share links (no account required).
* **Subtitles:** Automatic detection and loading of associated subtitles.
* **Performance:** Uses `mux.js` for extended compatibility.

## 🛠️ Architecture

* **Frontend:** Shaka Player (UI & Core), Mux.js.
* **Backend:** PHP (Nextcloud App Framework).
    * `PlayerController`: Handles authenticated access.
    * `ViewerController`: Handles public access (Share Tokens).
* **Mime-Types:** Automatic registration of MIME types for `.mpd` and `.m3u8` via migration classes.

## 📋 Prerequisites

* Nextcloud 24 to 32.
* Transcoded video files (via the *Video Converter* module).

## 👥 Authors
* Daniel Figueroa J (Original author)
* PFE Team (v2 Update and Refactor)