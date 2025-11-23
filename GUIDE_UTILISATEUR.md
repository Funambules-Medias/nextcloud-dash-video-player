# Guide Utilisateur - Lecteur Vidéo (v2)

Ce module permet de visionner les vidéos transcodées directement dans votre navigateur, en ajustant automatiquement la qualité selon votre connexion.

## 1. Visionner une vidéo (Usage interne)

Pour regarder une vidéo depuis votre compte Nextcloud :

1.  Allez dans l'application **Fichiers**.
2.  Naviguez vers le dossier contenant votre vidéo convertie.
3.  Repérez le fichier de manifeste **`.mpd`** (DASH) ou **`.m3u8`** (HLS).
4.  **Cliquez sur le fichier** : le lecteur s'ouvre automatiquement en plein écran.

### Fonctionnalités du lecteur
Le lecteur utilise **Shaka Player** pour offrir une expérience fluide :
* **Qualité Automatique :** La vidéo s'adapte à votre vitesse internet.
* **Sélection Manuelle :** Cliquez sur l'icône ⚙️ (roue dentée) pour forcer une résolution (ex: 1080p, 720p).
* **Sous-titres :** Cliquez sur l'icône 💬 (bulle) pour activer/désactiver les sous-titres (si disponibles).

## 2. Partager une vidéo (Public)

Vous pouvez partager une vidéo avec des personnes n'ayant pas de compte :

1.  Dans Fichiers, cliquez sur l'icône de **Partage** à côté du fichier `.mpd` (ou du dossier parent).
2.  Créez un **lien public** (Share link).
3.  Envoyez ce lien à votre destinataire.

Lorsqu'il ouvrira le lien, le **Viewer Public** se lancera automatiquement, lui permettant de regarder la vidéo avec les mêmes options de qualité que vous.