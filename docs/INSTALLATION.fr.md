# Guide d'installation — Conférences AdHoc WolfVoice pour OpenSimulator

**Version validée :** OpenSimulator **0.9.3.1**, **.NET 8**, module WebRTC/WolfVoice fondé sur la release [`v0.9.3.1-webrtc.2`](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.2). Tests concluants le **7 novembre 2026** sur BloodMoon avec des avatars présents sur **deux simulateurs différents**.

## Fonctionnement

- Conférences **IM à plusieurs avatars** dans Firestorm, indépendantes des groupes OpenSimulator.
- Chacun peut lire et écrire dans la conférence, sur un même simulateur **ou** sur plusieurs simulateurs.
- La **voix WebRTC** est optionnelle : personne n'est forcé de rejoindre un appel en entrant dans le chat écrit.
- Chacun peut raccrocher et revenir dans la voix sans quitter le chat écrit, sans déconnecter les autres.
- Les appels vocaux privés, la voix de proximité et la voix des groupes habituels restent disponibles avec la base WolfVoice compatible.

## Installer la DLL sur un simulateur

1. **Arrêter le simulateur** et sauvegarder sa DLL actuelle `bin/WebRtcVoiceRegionModule.dll`.
2. Télécharger l'archive de la dernière [release GitHub](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/releases).
3. Extraire et copier **uniquement `WebRtcVoiceRegionModule.dll`** dans le répertoire `bin/` de ce simulateur. **Ne pas remplacer les autres DLL.**
4. Dans le fichier `wolfvoice.ini` de **ce simulateur**, conserver les réglages déjà présents et ajouter dans la section `[WebRtcVoice]` :

   ```ini
   [WebRtcVoice]
   ConferenceApiUrl = https://bloodmoonpack.com/adhocconf/wolf_api.php
   ```

5. Conserver les fichiers de clé et la configuration `CredKeyFile` existants. Le correctif réutilise la clé de WolfVoice ; **aucune clé AdHoc supplémentaire** ni nouvelle section `[Wolf]` ne sont nécessaires par défaut.
6. Redémarrer le simulateur. Tester une conférence écrite avec plusieurs avatars puis un appel audio.
7. **Effectuer exactement la même opération sur le deuxième simulateur**, y compris la ligne `ConferenceApiUrl`. Les deux simulateurs doivent être autorisés à contacter le service central.

**Point confirmé pendant les tests :** si la DLL est installée sur deux simulateurs mais que `ConferenceApiUrl` manque sur l'un, son résident ne reçoit ni l'invitation ni les messages de la conférence. Ajouter la ligne et redémarrer a permis de valider le fonctionnement inter-simulateurs.

## Ce qu'il faut savoir avant d'utiliser l'URL BloodMoon

L'URL de l'exemple est **un service réellement hébergé par BloodMoon**, et non une API publique ouverte sans restrictions. Pour y accéder, ton simulateur doit faire partie des **serveurs de confiance autorisés**, disposer de la version compatible du module, et utiliser la clé de credential WolfVoice prévue pour ce service.

**Ne publie jamais les fichiers de clé ni leur contenu.** Le service repose actuellement sur une clé partagée et une liste d'adresses IP autorisées : cette combinaison **ne garantit pas l'identité d'un avatar** face à un administrateur de simulateur non fiable. Elle ne doit pas être ouverte à n'importe quelle grille sans renforcer l'authentification.

## Déployer son propre serveur de conférences

Le dépôt contient :
- `database/wolf_schema.sql` : tables MySQL `wolf_conf_sessions` et `wolf_conf_members` ;
- `web/wolf_api.php` : API de gestion et d'authentification des conférences ;
- `web/wolf_config.php.example` : modèle de configuration privée.

Ce backend est **adapté au fonctionnement de BloodMoon** : il consulte la table `wolfvoice_admin.servers` pour n'accepter que les adresses IP ayant le statut `allowed`. Sur un autre serveur, il faut disposer de cette structure ou adapter ce contrôle. Les scripts `web/conferences.php`, `web/config.php.example` et `database/schema.sql` sont d'anciens prototypes **à ne pas utiliser**.

Installation complète du serveur PHP/MySQL, exemples de droits et de sécurité : [documentation anglaise](INSTALLATION.md) et [architecture](ARCHITECTURE.md).

## En cas de problème

| Problème | Vérification |
| --- | --- |
| Invitation absente sur l'autre simulateur | DLL compatible **et** ligne `ConferenceApiUrl` sur **chaque** simulateur, puis redémarrage |
| La voix fonctionne mais pas les IM écrits | Vérifier la version du module et le routage des IM entre simulateurs |
| Message `no ConferenceApiUrl` dans le journal | La configuration n'est pas lue, manque dans `[WebRtcVoice]` ou nécessite un redémarrage |
| Erreur `403` API | Vérifier IP autorisée et correspondance des clés sans divulguer celles-ci |
| Refus de rejoindre la voix | Vérifier les droits, la session, la clé, et si l'avatar n'a pas quitté la conférence **écrite** |
| Installation problématique | Restaurer la DLL sauvegardée et reprendre la procédure |

## Versions et compatibilité

Le module public provient de **`v0.9.3.1-webrtc.2`**, avec les **sept correctifs** du présent dépôt. Il ne s'agit ni d'un module autonome à installer à côté d'une autre version, ni d'une DLL générique pour toute version OpenSimulator.

[Releases GitHub](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/releases) · [README anglais](../README.md) · [Compilation #30 validée](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/actions/runs/42395627678).

## Remerciements

Un grand merci à **Lone Wolf de WolfGrid** pour son travail sur WolfVoice, ainsi qu'aux communautés **OpenSimulator** et **Firestorm**.
