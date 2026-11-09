# Guide d'installation — Conférences AdHoc WolfVoice pour OpenSimulator

> **Installation complète recommandée :** utiliser [BloodMoon Voice v1.0.0](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/releases/tag/v1.0.0), les **trois paires DLL/PDB de [OpenSimulator v0.9.3.1-webrtc.3](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3)** et, séparément, l'API PHP/MySQL de ce dépôt. **La DLL régionale de la release .3 intègre déjà les correctifs AdHoc : ne pas la remplacer par l'ancienne DLL indépendante.** Voir le [guide complet des trois projets](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/COMPLETE_STACK.md). Les indications sur la base .2 ci-dessous décrivent la compilation historique du correctif.

**Version complète recommandée :** OpenSimulator **0.9.3.1**, **.NET 8**, modules [`v0.9.3.1-webrtc.3`](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3). La release `.2` n'est que la base historique de compilation. Tests concluants le **7 novembre 2026** sur BloodMoon avec des avatars présents sur **deux simulateurs différents**.

## Installation recommandée pour un nouvel utilisateur

**Utilise les trois éléments compatibles, pas trois jeux de DLL** : [serveur BloodMoon Voice v1.0.0](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/releases/tag/v1.0.0), **les trois paires DLL/PDB de [OpenSimulator v0.9.3.1-webrtc.3](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3)**, et **l'API PHP/MySQL de ce dépôt à installer à part**. La DLL de conférence est déjà intégrée à la release `.3`. **Ne pas installer une DLL plus ancienne par-dessus.** [Guide complet](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/COMPLETE_STACK.md).

L'adresse `https://conference.example.org/adhocconf/wolf_api.php` est **un exemple fictif** : remplace-la par le domaine HTTPS qui héberge **ton propre** `wolf_api.php`. La véritable API BloodMoon n'est pas accessible librement. Renseigne `ConferenceApiUrl` sur **tous** les simulateurs participants.

Les anciennes indications de remplacement d'une DLL unique ci-dessous concernent les installations historiques basées sur `.2`, pas une nouvelle installation complète en `.3`.

## Fonctionnement

- Conférences **IM à plusieurs avatars** dans Firestorm, indépendantes des groupes OpenSimulator.
- Chacun peut lire et écrire dans la conférence, sur un même simulateur **ou** sur plusieurs simulateurs.
- La **voix WebRTC** est optionnelle : personne n'est forcé de rejoindre un appel en entrant dans le chat écrit.
- Chacun peut raccrocher et revenir dans la voix sans quitter le chat écrit, sans déconnecter les autres.
- Les appels vocaux privés, la voix de proximité et la voix des groupes habituels restent disponibles avec la base WolfVoice compatible.

## Procédure recommandée : installation complète

1. Installer [BloodMoon Voice v1.0.0](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/releases/tag/v1.0.0) sur son propre serveur en suivant le [guide français complet](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/INSTALLATION_FR.md).
2. Arrêter chaque simulateur et sauvegarder sa configuration, ses clés et ses anciennes DLL. Installer les **trois paires DLL/PDB** de [OpenSimulator v0.9.3.1-webrtc.3](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3).
3. **Ne pas ajouter l'ancienne DLL régionale de la release AdHoc v0.1.0** : la bonne version est déjà dans le ZIP OpenSimulator `.3`.
4. Installer l'API centrale PHP/MySQL de ce dépôt (section suivante) sur son propre serveur web HTTPS.
5. Dans `[WebRtcVoice]` de **chaque simulateur**, conserver les clés existantes et ajouter une URL pointant vers **sa propre API** :

```ini
ConferenceApiUrl = https://conference.example.org/adhocconf/wolf_api.php
```

**Adresse fictive :** `conference.example.org` est un simple exemple et doit être remplacé par le domaine réel hébergeant `wolf_api.php`. Ce n'est **pas** l'API de BloodMoon. Le service privé de BloodMoon n'est pas ouvert aux serveurs externes non autorisés.

6. Redémarrer les simulateurs. Tester les invitations IM, les messages et l'audio facultatif, d'abord sur un seul simulateur puis entre deux simulateurs différents.

## Installation historique d'une DLL régionale seule

Le ZIP DLL du dépôt AdHoc est conservé pour les développeurs qui reconstruisent l'ancien système depuis la base `.2`. **Ne pas l'utiliser pour une nouvelle installation complète basée sur `.3`.**

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

Le module régional historique de ce dépôt provient de la base **`v0.9.3.1-webrtc.2`**, avec les correctifs du présent dépôt. Pour une nouvelle installation, préférer le pack complet **`v0.9.3.1-webrtc.3`**. Il ne s'agit ni d'un module autonome à installer à côté d'une autre version, ni d'une DLL générique pour toute version OpenSimulator.

[Releases GitHub](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/releases) · [README anglais](../README.md) · [Compilation #30 validée](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/actions/runs/42395627678).

## Remerciements

Un grand merci à **Lone Wolf de WolfGrid** pour son travail sur WolfVoice, ainsi qu'aux communautés **OpenSimulator** et **Firestorm**.
