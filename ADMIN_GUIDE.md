# Administration & Installation Guide — PLAYER

This guide is intended for the person deploying the **PLAYER** application on a Nextcloud instance.

## 📦 Installation

### 1. Deployment

Clone the repository into your Nextcloud instance’s `apps` directory.

**Important:** The destination folder must be named **`dashvideoplayerv2`** to match the application ID declared in `info.xml`.

> cd /var/www/nextcloud/apps
>
> git clone https://github.com/Funambules-Medias/nextcloud-dash-video-player dashvideoplayerv2
>
> chown -R www-data:www-data dashvideoplayerv2

### 2. Activation

Enable the app using `occ`.

> sudo -u www-data php /var/www/nextcloud/occ app:enable dashvideoplayerv2

### 3. Updating mime types

The application registers file types associated with `.mpd` and `.m3u8`. To ensure Nextcloud recognizes these files properly in the UI, updating the MIME type database may be required after installation.

> sudo -u www-data php /var/www/nextcloud/occ maintenance:mimetype:update-db
>
> sudo -u www-data php /var/www/nextcloud/occ maintenance:mimetype:update-js

---

## 🛠️ Troubleshooting

### Player does not open

If clicking a `.mpd` or `.m3u8` file **downloads it** instead of opening the player:

1. Check that the `dashvideoplayerv2` app is enabled.
2. Verify that MIME types were correctly updated on the server side.
3. Clear your browser cache.

### Public sharing

If a public share link triggers a direct download of the manifest rather than opening the player, also verify that the **full folder** containing the required files is shared (not only the single file). This behavior is detailed in the user guide.

---

## 📋 Compatibility

Application **tested on Nextcloud 32**.

---

## 👥 Team

PFE project by:
- Simon Bigonnesse
- Abdessamad Cherifi
- Clément Deffes
- Nicolas Thibodeau (Team lead)

Supervised by **Stéphane Coulombe**.
