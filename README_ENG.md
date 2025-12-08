# PLAYER (PFE)

A Nextcloud application integrating **Shaka Player** for native playback of adaptive video streams (MPEG-DASH and HLS) directly from the Files interface.

## 🌟 Features

* **Adaptive Playback:** Supports `.mpd` (DASH) and `.m3u8` (HLS) manifests.
* **Native Integration:** Opens directly upon clicking a file in the Files app (in a dedicated player view).
* **Exit the player:** Clicking outside the player area closes/exits the viewer.
* **Public Support:** Enables playback via a public share link **when the full folder** containing the required video/manifests is shared (sharing only the file may not work).
* **Subtitles:** Automatic detection and loading of associated subtitles.
* **Performance:** Uses `mux.js` for extended compatibility.

## 🛠️ Architecture

* **Frontend:** Shaka Player (UI & Core), Mux.js.
* **Backend:** PHP (Nextcloud App Framework).
    * `PlayerController`: Handles authenticated access.
    * `ViewerController`: Handles public access (Share Tokens).
* **Mime-Types:** Automatic registration of MIME types for `.mpd` and `.m3u8` via migration classes.

## 📋 Prerequisites

* **Tested on Nextcloud 32.**
* Transcoded video files (via the *Video Converter* module).

## 👥 Authors

* Daniel Figueroa J (Original author)
* PFE Team (v2 update and refactor):
    * Simon Bigonnesse
    * Abdessamad Cherifi
    * Clément Deffes
    * Nicolas Thibodeau (Team lead)
    * Supervised by **Stéphane Coulombe** (Professor)
