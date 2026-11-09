# Conférences AdHoc pour OpenSimulator 🎙️

**Conversations IM à plusieurs avatars, avec appels vocaux WebRTC facultatifs — OpenSimulator 0.9.3.1 / .NET 8.**

[English README](README.md) · [Installation française de l'API](docs/INSTALLATION.fr.md) · [Guide complet de BloodMoon Voice](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/INSTALLATION_FR.md)

Ce dépôt fournit **l'API centrale PHP/MySQL des conférences AdHoc** ainsi que les correctifs et sources de leur intégration dans OpenSimulator. Il n'est **pas** le serveur vocal lui-même : celui-ci se trouve dans le dépôt [BloodMoon-Voice](https://github.com/Vlad-BloodMoon/BloodMoon-Voice).

## Fonctionnalités

- Créer une conférence **IM écrite entre plusieurs avatars**, sans appartenir au même groupe OpenSimulator.
- Inviter et échanger des messages entre avatars présents sur **un ou plusieurs simulateurs**.
- Démarrer un appel **WebRTC facultatif** depuis une conférence écrite.
- Raccrocher et rejoindre la voix de façon indépendante, sans fermer la conférence IM ni interrompre les autres.
- Conserver les appels vocaux privés, la voix locale et la voix des groupes OpenSimulator avec le serveur et les DLL compatibles.
- Gérer les membres et sessions dans un **registre central PHP/MySQL**, distinct du transport audio.

Fonctionnalités testées sur BloodMoon avec des viewers Firestorm compatibles, y compris entre deux simulateurs. Il ne s'agit pas d'une certification de toutes les installations externes.

## Les trois composants nécessaires

| Ordre | Projet | Rôle |
| --- | --- | --- |
| **1** | [BloodMoon Voice v1.0.0](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/releases/tag/v1.0.0) | Serveur vocal WebRTC indépendant |
| **2** | [OpenSimulator v0.9.3.1-webrtc.3](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3) | Trois paires DLL/PDB compatibles, à installer sur chaque simulateur |
| **3** | **Ce dépôt OpenSimulator-AdHocConferences** | API et base PHP/MySQL à installer séparément |

**Attention : la DLL régionale AdHoc est déjà incluse dans le pack OpenSimulator `.3`. Ne la remplace pas par l'ancienne DLL autonome de notre release historique `v0.1.0`.** Cette ancienne archive est conservée pour les développeurs travaillant à partir de la version de base `.2`.

## Installation de l'API indépendante

1. Préparer un hébergement HTTPS, PHP 8.3+ avec PDO MySQL et MySQL 8.
2. Importer `database/wolf_schema.sql` dans une base dédiée.
3. Installer `web/wolf_api.php` et créer le fichier privé `wolf_config.php` à partir de `web/wolf_config.php.example`.
4. Configurer la clé existante de credential et le contrôle des simulateurs autorisés. **La version actuelle de l'API dépend de la table `wolfvoice_admin.servers`, à fournir ou à adapter.** Ne pas se contenter d'importer les tables de conférences.
5. Sur **chaque simulateur**, dans `[WebRtcVoice]`, ajouter l'adresse de **sa propre** API :

```ini
ConferenceApiUrl = https://conference.example.org/adhocconf/wolf_api.php
```

**Cette URL est fictive** : remplacer le domaine et le chemin par ceux où `wolf_api.php` est réellement hébergé. L'API de production BloodMoon n'est pas un service ouvert au public.

6. Redémarrer, tester une conférence écrite, lancer la voix facultative, puis répéter entre deux simulateurs.

**[Guide français détaillé pour déployer l'API, la base et les autorisations](docs/INSTALLATION.fr.md)** · [Guide du système vocal complet](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/INSTALLATION_FR.md)

## Sécurité et limites

Le fonctionnement actuel réutilise une clé de credential partagée pour signer des requêtes. L'accès doit être réservé aux administrateurs de simulateurs **de confiance**. Une IP autorisée et une signature valide n'authentifient pas indépendamment l'avatar : ne pas ouvrir ce service à des administrateurs inconnus sans renforcer ce mécanisme.

Ne jamais publier les clés privées, les mots de passe ou les fichiers de configuration opérationnelle. La version actuelle du backend est adaptée à l'architecture BloodMoon et peut demander des ajustements pour une grille indépendante.

## Remerciements

Travail réalisé à partir de WolfVoice, avec les contributions de ses auteurs et de Lone Wolf (WolfGrid), d'OpenSimulator et des utilisateurs ayant participé aux tests BloodMoon. Les sources et correctifs restent disponibles dans ce dépôt.
