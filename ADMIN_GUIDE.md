# Administration & Installation Guide

## 📦 Installation

### 1. Deployment
Clone the repository into your Nextcloud instance's `apps` directory.
**Important:** The destination folder MUST be named `dashvideoplayerv2` to match the application ID.

```bash
cd /var/www/nextcloud/apps
# The target folder MUST be named 'dashvideoplayerv2' to match the app ID
git clone [VOTRE_URL_GIT_ICI] dashvideoplayerv2
chown -R www-data:www-data dashvideoplayerv2
```

### 2. Activation
Enable the app via the command line.

```bash
# The ID is 'dashvideoplayerv2' (confirmed by info.xml)
sudo -u www-data php /var/www/nextcloud/occ app:enable dashvideoplayerv2
```

### 3. Updating Mime-Types
The application registers new file types (`application/dash+xml` and `application/x-mpegURL`). For Nextcloud to recognize them correctly, it is **highly recommended** to update the MIME type database after installation:

```bash
# Update the database to include .mpd and .m3u8 mime types
sudo -u www-data php /var/www/nextcloud/occ maintenance:mimetype:update-db

# Update the JS mapping so the icon and action appear correctly in the browser
sudo -u www-data php /var/www/nextcloud/occ maintenance:mimetype:update-js
```

---

## 🛠️ Configuration & Troubleshooting

### CORS Configuration (External Storage)
If your videos are stored on external storage (S3, MinIO, FTP), you must configure CORS headers on that server to allow your Nextcloud domain. Without this, the player will show a network error.

**Required Headers:**
* `Access-Control-Allow-Origin: https://your-nextcloud.com`
* `Access-Control-Allow-Methods: GET, HEAD, OPTIONS`

### Troubleshooting: Player does not open
If clicking on a `.mpd` file downloads it instead of opening the player:
1.  Check that the app `dashvideoplayerv2` is enabled.
2.  Re-run the mimetype update commands (see Installation section).
3.  Clear your browser cache.