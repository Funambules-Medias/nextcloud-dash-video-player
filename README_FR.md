# PLAYER (PFE)

Une application Nextcloud intégrant **Shaka Player** pour la lecture native de flux vidéo adaptatifs (MPEG-DASH et HLS) directement depuis l'interface de fichiers.

## 🌟 Fonctionnalités

* **Lecture adaptative :** Supporte les manifestes `.mpd` (DASH) et `.m3u8` (HLS).
* **Intégration native :** S'ouvre directement au clic sur un fichier dans l'application Fichiers (dans une vue dédiée du lecteur).
* **Quitter le lecteur :** Un clic à l’extérieur du lecteur permet de quitter la lecture.
* **Support public :** Permet la lecture via un lien de partage public **lorsque le dossier complet** contenant la vidéo/manifestes est partagé (le partage du seul fichier peut ne pas fonctionner).
* **Sous-titres :** Détection et chargement automatique des sous-titres associés.
* **Performance :** Utilisation de `mux.js` pour une compatibilité étendue.

## 🛠️ Architecture

* **Frontend :** Shaka Player (UI & Core), Mux.js.
* **Backend :** PHP (Nextcloud App Framework).
    * `PlayerController` : Gestion des accès authentifiés.
    * `ViewerController` : Gestion des accès publics (Share Tokens).
* **Mime-Types :** Enregistrement automatique des types MIME pour `.mpd` et `.m3u8` via les classes de migration.

## 📋 Pré-requis

* **Testé sur Nextcloud 32.**
* Fichiers vidéo transcodés (via le module *Video Converter*).

## 👥 Auteurs

* Daniel Figueroa J (Auteur original)
* Équipe PFE (mise à jour et refonte v2) :
    * Simon Bigonnesse
    * Abdessamad Cherifi
    * Clément Deffes
    * Nicolas Thibodeau (chef d'équipe)
    * Sous la supervision de **Stéphane Coulombe** (professeur)
