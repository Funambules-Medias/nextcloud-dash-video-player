# User Guide - Video Player (v2)

This module allows you to watch transcoded videos directly in your browser, automatically adjusting quality based on your connection speed.

## 1. Watching a Video (Internal Use)

To watch a video from your Nextcloud account:

1.  Go to the **Files** app.
2.  Navigate to the folder containing your converted video.
3.  Locate the **`.mpd`** (DASH) or **`.m3u8`** (HLS) manifest file.
4.  **Click on the file**: The player will open automatically in full screen.

### Player Features
The player uses **Shaka Player** to ensure a smooth experience:
* **Auto Quality:** The video adapts to your internet speed.
* **Manual Selection:** Click the ⚙️ (gear icon) to force a specific resolution (e.g., 1080p, 720p).
* **Subtitles:** Click the 💬 (bubble icon) to enable/disable subtitles (if available).

## 2. Sharing a Video (Public)

You can share a video with external users:

1.  In Files, click the **Share** icon next to the `.mpd` file (or the parent folder).
2.  Create a **Public Link**.
3.  Send this link to your recipient.

When they open the link, the **Public Viewer** will launch automatically, allowing them to watch the video with the same quality options available to you.