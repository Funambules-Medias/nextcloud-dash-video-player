# Guide d'Administration & Installation

## 📦 Installation

### 1. Déploiement
Cloner le dépôt dans le dossier `apps` de votre instance Nextcloud.
**Important :** Le dossier de destination DOIT se nommer `dashvideoplayerv2` pour correspondre à l'ID de l'application.

```bash
cd /var/www/nextcloud/apps
# Le dossier cible DOIT s'appeler 'dashvideoplayerv2' pour correspondre à l'ID de l'app
git clone [VOTRE_URL_GIT_ICI] dashvideoplayerv2
chown -R www-data:www-data dashvideoplayerv2
```

### 2. Activation
Activer l'application via la ligne de commande.

```bash
# L'ID est 'dashvideoplayerv2' (confirmé par info.xml)
sudo -u www-data php /var/www/nextcloud/occ app:enable dashvideoplayerv2
```

### 3. Mise à jour des Mime-Types
L'application enregistre de nouveaux types de fichiers (`application/dash+xml` et `application/x-mpegURL`). Pour que Nextcloud les reconnaisse correctement, il est **fortement recommandé** de mettre à jour la base de données des types MIME après l'installation :

```bash
# Met à jour la base de données pour inclure .mpd et .m3u8
sudo -u www-data php /var/www/nextcloud/occ maintenance:mimetype:update-db

# Met à jour le mapping JS pour que l'icône et l'action s'affichent dans le navigateur
sudo -u www-data php /var/www/nextcloud/occ maintenance:mimetype:update-js
```

---

## 🛠️ Configuration & Dépannage

### Configuration CORS (Stockage Externe)
Si vos vidéos sont stockées sur un serveur externe (S3, MinIO, FTP), vous devez configurer les en-têtes CORS sur ce serveur pour autoriser votre domaine Nextcloud. Sans cela, le lecteur affichera une erreur réseau.

**En-têtes requis :**
* `Access-Control-Allow-Origin: https://votre-nextcloud.com`
* `Access-Control-Allow-Methods: GET, HEAD, OPTIONS`

### Dépannage : Le lecteur ne s'ouvre pas
Si le clic sur un fichier `.mpd` télécharge le fichier au lieu d'ouvrir le lecteur :
1.  Vérifiez que l'application `dashvideoplayerv2` est bien activée.
2.  Relancez les commandes de mise à jour des mimetypes (voir section Installation).
3.  Videz le cache du navigateur.