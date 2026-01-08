# Guide Utilisateur - PLAYER

Ce module permet de visionner les vidéos transcodées directement dans votre navigateur, en ajustant automatiquement la qualité selon votre connexion.

## 1. Visionner une vidéo (Usage interne)

Pour regarder une vidéo depuis votre compte Nextcloud :

1. Allez dans l'application **Fichiers**.
2. Naviguez vers le dossier contenant votre vidéo convertie.
3. Repérez le fichier de manifeste **`.mpd`** (DASH) ou **`.m3u8`** (HLS).
4. **Cliquez sur le fichier** : le lecteur s'ouvre automatiquement dans une vue dédiée.

![Video Player](docs/screenshot/screenshot.png)

### Fonctionnalités du lecteur

Le lecteur utilise **Shaka Player** pour offrir une expérience fluide :

* **Qualité Automatique :** La vidéo s'adapte à votre vitesse internet.
* **Sélection Manuelle :** Cliquez sur l'icône ⚙️ (roue dentée) pour forcer une résolution (ex: 1080p, 720p).
* **Sous-titres :** Cliquez sur l'icône 💬 (bulle) pour activer/désactiver les sous-titres (si disponibles).
* **Quitter le lecteur :** Un clic à l’extérieur du lecteur permet de quitter la lecture.

## 2. Partager une vidéo (Public)

Vous pouvez partager une vidéo avec des personnes n'ayant pas de compte Nextcloud.

### Étapes

1. Dans **Fichiers**, partagez **le dossier complet** qui contient :
    - le manifeste `.mpd` et/ou `.m3u8`,
    - les segments vidéo associés,
    - et, s’il y en a, les sous-titres.
2. Créez un **lien public** sur ce dossier.
3. Envoyez ce lien à votre destinataire.

**Important :** Le lien public fonctionne lorsque le **dossier complet** contenant les fichiers nécessaires est partagé. Le partage du seul fichier peut ne pas permettre la lecture.

### Côté visiteur

1. Le visiteur ouvre le lien public du dossier.
2. Il **clique sur le fichier** `Film.mpd` ou `Film.m3u8`.
3. Le lecteur s’ouvre et la vidéo peut être visionnée.

## 3. Dépannage rapide

* **Le lien public télécharge un fichier** (`.mpd` ou `.m3u8`) au lieu d’ouvrir le lecteur : c’est généralement un mauvais signe. Cela peut indiquer que le lecteur n’est pas correctement associé au type de fichier ou que le partage n’inclut pas le dossier complet.

## 4. À propos

Application **testée sur Nextcloud 32**.

Projet PFE réalisé par :
* Simon Bigonnesse
* Abdessamad Cherifi
* Clément Deffes
* Nicolas Thibodeau (chef d’équipe)

Sous la supervision de **Stéphane Coulombe**.
