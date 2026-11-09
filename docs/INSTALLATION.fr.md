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

## Installation de l'API PHP/MySQL sur son propre serveur — pas à pas

Cette procédure s'adresse à un administrateur disposant **déjà** d'un serveur Ubuntu avec HTTPS, PHP 8.3 ou supérieur, PDO MySQL et MySQL 8. Ne pas exécuter ces commandes sur la production BloodMoon. Sauvegarder les configurations existantes avant toute modification. Ne jamais enregistrer de clés dans GitHub.

### 1. Préparer les fichiers

Cloner **ce dépôt** sur le serveur qui héberge l'API :

```bash
git clone https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences.git
cd OpenSimulator-AdHocConferences
```

Les fichiers à utiliser sont `database/wolf_schema.sql`, `web/wolf_api.php` et `web/wolf_config.php.example`. Ils **ne contiennent pas** le binaire vocal ni les modules du simulateur. Ne pas installer les anciens prototypes.

### 2. Préparer la base MySQL

Dans une session MySQL d'administration, créer une base et un utilisateur dédiés (remplacer le mot de passe d'exemple) :

```sql
CREATE DATABASE opensim_adhoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'adhoc_user'@'localhost' IDENTIFIED BY 'REMPLACER_PAR_UN_SECRET_FORT';
GRANT SELECT, INSERT, UPDATE, DELETE ON opensim_adhoc.* TO 'adhoc_user'@'localhost';
```

Importer **le schéma fourni par ce dépôt** :

```bash
mysql -u root -p opensim_adhoc < database/wolf_schema.sql
```

**Installation autonome :** dans `wolf_config.php`, définir `'authorization_source' => 'local'` et renseigner `'trusted_simulator_ips' => ['IP_PUBLIQUE_DU_SIMULATEUR']` (remplacer par la véritable IP sortante de chaque simulateur). **Aucune table `wolfvoice_admin.servers` n'est nécessaire dans ce mode.** Le mode historique `'bloodmoon_db'` continue d'utiliser cette table pour les installations BloodMoon existantes. Sans liste d'IP autorisées, l'API refuse toutes les requêtes.

### 3. Configurer PHP et l'hébergement HTTPS

Installer `web/wolf_api.php` derrière le domaine HTTPS choisi (par exemple `https://conference.example.org/adhocconf/wolf_api.php`). Copier `web/wolf_config.php.example` sous le nom `wolf_config.php` dans le même dossier, puis définir le DSN, le mot de passe SQL, le chemin réel du fichier de clé existante `CredKeyFile` et les autres paramètres.

Le fichier `wolf_config.php` doit être **illisible depuis le web** et lisible uniquement par le service PHP approprié. Ne pas laisser des mots de passe ou clés dans un dépôt public. Le processus PHP doit pouvoir lire la **même clé de credential** que les simulateurs et le serveur vocal, sans jamais la publier.

Configurer le serveur HTTPS et le pare-feu en n'autorisant que les simulateurs **de confiance**. Le contrôle logiciel consulte l'IP source de la requête et le registre d'autorisation ; il ne suffit pas d'avoir une URL connue pour être accepté.

### 4. Configurer tous les simulateurs

Sur **chaque** OpenSimulator 0.9.3.1 / .NET 8 participant, utiliser exclusivement les DLL/PDB de la release `v0.9.3.1-webrtc.3`. Dans `bin/config/wolfvoice.ini`, conserver les réglages vocaux existants et définir :

```ini
[WebRtcVoice]
ConferenceApiUrl = https://conference.example.org/adhocconf/wolf_api.php
```

**Remplacer `conference.example.org` par le vrai nom DNS de ton serveur web et adapter le chemin.** Cet exemple ne désigne aucun service en fonctionnement. Le service BloodMoon n'est pas ouvert au public. Redémarrer **tous** les simulateurs.

### 5. Vérifications et erreurs courantes

- Une requête HTTP GET vers `wolf_api.php` renvoie normalement **405 (POST requis)** : cela indique seulement que la route répond, pas que la conférence est opérationnelle.
- Une erreur **403** peut indiquer une IP non autorisée ou une signature/clés incompatibles.
- Une erreur **503** peut indiquer une base inaccessible ou la clé indisponible.
- Vérifier ensuite dans Firestorm une conférence écrite entre plusieurs avatars, puis l'audio facultatif. Refaire avec deux avatars sur **deux simulateurs différents**.
- Si les invitations n'arrivent pas sur la deuxième région : contrôler la DLL `.3`, `ConferenceApiUrl`, la liste blanche et le transfert des IM OpenSimulator sur **les deux régions**.

**Sécurité :** le secret partagé permet à un administrateur de simulateur de signer des actions pour des UUID d'avatars. Ce modèle ne convient qu'à des administrateurs de confiance et ne prouve pas indépendamment l'identité de l'avatar. Ne pas ouvrir cette API à des tiers non fiables sans repenser l'authentification.

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
