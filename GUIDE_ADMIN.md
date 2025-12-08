# Guide d'Administration & Installation — PLAYER

Ce guide est destiné à la personne qui déploie l’application **PLAYER** sur une instance Nextcloud.

## 📦 Installation

### 1. Déploiement

Cloner le dépôt dans le dossier `apps` de votre instance Nextcloud.

**Important :** Le dossier de destination doit se nommer **`dashvideoplayerv2`** pour correspondre à l’ID de l’application déclaré dans `info.xml`.

> cd /var/www/nextcloud/apps
>
> git clone https://github.com/Funambules-Medias/nextcloud-dash-video-player dashvideoplayerv2
> 
> chown -R www-data:www-data dashvideoplayerv2


### 2. Activation

Activer l’application via `occ`.

> sudo -u www-data php /var/www/nextcloud/occ app:enable dashvideoplayerv2

### 3. Mise à jour des mime-types

L’application enregistre des types de fichiers associés à `.mpd` et `.m3u8`. Pour s’assurer que Nextcloud reconnaît correctement ces fichiers dans l’interface, une mise à jour de la base des mime-types peut être nécessaire après l’installation.

> sudo -u www-data php /var/www/nextcloud/occ maintenance:mimetype:update-db
> 
> sudo -u www-data php /var/www/nextcloud/occ maintenance:mimetype:update-js

---

## 🛠️ Dépannage

### Le lecteur ne s’ouvre pas

Si le clic sur un fichier `.mpd` ou `.m3u8` **télécharge le fichier** au lieu d’ouvrir le lecteur :

1. Vérifiez que l’application `dashvideoplayerv2` est bien activée.
2. Vérifiez que les mime-types ont bien été pris en compte côté serveur.
3. Videz le cache du navigateur.

### Partage public

Si un lien de partage public entraîne un téléchargement direct du manifeste plutôt que l’ouverture du lecteur, vérifiez aussi que le **dossier complet** contenant les fichiers nécessaires est partagé (et pas uniquement le fichier). Cette configuration est décrite dans le guide utilisateur.

---

## 📋 Compatibilité

Application **testée sur Nextcloud 32**.

---

## 👥 Équipe

Projet PFE réalisé par :
- Simon Bigonnesse
- Abdessamad Cherifi
- Clément Deffes
- Nicolas Thibodeau (chef d’équipe)

Sous la supervision de **Stéphane Coulombe**.
