# Dash Video Player v2 (PFE)

Une application Nextcloud intégrant **Shaka Player** pour la lecture native de flux vidéo adaptatifs (MPEG-DASH et HLS) directement depuis l'interface de fichiers.

## 🌟 Fonctionnalités

* **Lecture Adaptative :** Supporte les manifestes `.mpd` (DASH) et `.m3u8` (HLS).
* **Intégration Native :** S'ouvre directement au clic sur un fichier dans l'application Fichiers.
* **Support Public :** Permet la lecture des vidéos via les liens de partage publics Nextcloud (sans compte).
* **Sous-titres :** Détection et chargement automatique des sous-titres associés.
* **Performance :** Utilisation de `mux.js` pour une compatibilité étendue.

## 🛠️ Architecture

* **Frontend :** Shaka Player (UI & Core), Mux.js.
* **Backend :** PHP (Nextcloud App Framework).
    * `PlayerController` : Gestion des accès authentifiés.
    * `ViewerController` : Gestion des accès publics (Share Tokens).
* **Mime-Types :** Enregistrement automatique des types MIME pour `.mpd` et `.m3u8` via les classes de migration.

## 📋 Pré-requis

* Nextcloud 24 à 32.
* Fichiers vidéo transcodés (via le module *Video Converter*).

## 👥 Auteurs
* Daniel Figueroa J (Auteur original)
* Équipe PFE (Mise à jour et refonte v2)