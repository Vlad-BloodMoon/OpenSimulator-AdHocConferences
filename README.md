# OpenSimulator Ad-Hoc Conferences · WolfVoice WebRTC

> **Recommended complete BloodMoon deployment (November 2026):** use [BloodMoon Voice v1.0.0](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/releases/tag/v1.0.0) with the **exact complete [OpenSimulator v0.9.3.1-webrtc.3 module ZIP](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3)**, whose regional DLL **already incorporates** the tested AdHoc changes. **Do not replace that .3 regional DLL with the older standalone DLL from this project's historical release.** For your own conference service, separately deploy this repository's current PHP/MySQL API. [Full three-project installation](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/COMPLETE_STACK.md). The older .2 references below describe the original patch/build baseline, not the recommended complete .3 deployment.

**Multi-avatar instant-message conferences with optional WebRTC voice calls for OpenSimulator 0.9.3.1 / .NET 8.** Supports residents on **different simulator processes/regions**, using the existing WolfVoice voice integration.

[![Build and integration checks](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/actions/workflows/check.yml/badge.svg)](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/actions/workflows/check.yml)
[![GitHub releases](https://img.shields.io/github/v/release/Vlad-BloodMoon/OpenSimulator-AdHocConferences?include_prereleases)](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/releases)

**Tags / keywords:** OpenSimulator, OpenSim, WebRTC, WolfVoice, voice chat, VoIP, group voice, ad-hoc conference, IM, instant messaging, virtual worlds, Firestorm, .NET 8, Linux.

## What it does

- Starts an **ad-hoc group IM** directly in Firestorm, with multiple avatars (not a land/parcel chat and **not** an OpenSimulator group membership).
- Shares invitations and text messages among participants on **one or multiple simulators**.
- Lets participants join an optional **WolfVoice WebRTC conference call**, hang up independently, and rejoin without leaving the text IM.
- Prevents duplicate outgoing messages, recursive voice ringing, and unnecessary call notifications to participants who are already connected.
- Preserves the existing WebRTC spatial/parcel voice, private voice calls, and normal group voice from the pinned upstream build.

### Real-world validation (BloodMoon, 7 November 2026)

**Tested successfully with Firestorm:** multi-avatar IM creation, text exchange without sender echo, voice invitations, individual hang-up/rejoin without forcing others out of voice, and **cross-simulator invitations/text/voice**. The cross-simulator test required `ConferenceApiUrl` on **both** simulators. This is an initial public release of the **BloodMoon-tested integration**, not a claim of compatibility with every OpenSimulator distribution/viewer or an independent security audit.

## Installation recommandée — pas à pas

Pour une installation neuve, suivre le [guide complet BloodMoon Voice](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/COMPLETE_STACK.md), puis le [guide d'installation de l'API](docs/INSTALLATION.fr.md).

1. Installer [BloodMoon Voice v1.0.0](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/releases/tag/v1.0.0) sur son propre serveur vocal.
2. Sur **chaque simulateur**, installer les trois paires DLL/PDB de la [release OpenSimulator v0.9.3.1-webrtc.3](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3). La DLL AdHoc testée est **déjà dedans** : ne pas installer le vieux ZIP autonome de ce dépôt par-dessus.
3. Sur son serveur web, installer l'API PHP/MySQL depuis `web/wolf_api.php`, `web/wolf_config.php.example` et `database/wolf_schema.sql`. Vérifier la dépendance à `wolfvoice_admin.servers` dans le [guide détaillé](docs/INSTALLATION.fr.md).
4. Dans `[WebRtcVoice]` de **chaque simulateur**, renseigner **sa propre** adresse :

   ```ini
   ConferenceApiUrl = https://conference.example.org/adhocconf/wolf_api.php
   ```

   **Attention :** `conference.example.org` est un exemple fictif, qui ne fonctionnera pas sans être remplacé par votre domaine HTTPS hébergeant `wolf_api.php`. L'API BloodMoon en production n'est pas ouverte au public.
5. Vérifier l'autorisation des IP et la clé privée commune, redémarrer, puis tester conférence texte et voix entre deux simulateurs.

**À propos de [l'ancienne release AdHoc v0.1.0](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/releases/tag/v0.1.0) :** son ZIP de DLL régionale est conservé pour l'historique et les développeurs qui reconstruisent à partir de la base `.2`. **Ce n'est PAS le paquet à installer pour une installation complète basée sur `.3`.**

## Architecture

```text
Firestorm A ── OpenSimulator A ─┐
                               ├── HTTPS signed requests ── Central PHP API ── MySQL registry
Firestorm B ── OpenSimulator B ─┘
       │              │
       └── multi-avatar IM routing ── OpenSimulator grid IM service (signed internal envelope)
       └── optional voice calls ───── WolfVoice WebRTC server (conf-<session UUID>)
```

- The **PHP/MySQL registry** stores active conference sessions and member state.
- Each **simulator module** handles viewer chat-session capabilities, signed internal IMs, and per-avatar voice credentials.
- The **OpenSimulator IM transfer module** delivers internal conference events between simulator processes; a recipient must be a **root agent** on the destination simulator.
- The **WolfVoice server** carries audio; the PHP API is **not** the media server.
- A text conference can exist **without a voice call**.

See [Architecture, deployment and security](docs/ARCHITECTURE.md).

## Source tree

| Path | Description |
| --- | --- |
| `patches/wolfconference-voice-key-and-root.patch` | Reuse existing credentials and send internal IM to root avatar |
| `patches/wolfconference-text-diagnostics.patch` | Multi-avatar IM diagnostics |
| `patches/wolfconference-text-entry.patch` | Ensure conference text reaches the module |
| `patches/wolfconference-text-no-self-echo.patch` | Avoid duplicate local sender text |
| `patches/wolfconference-voice-invite-loop.patch` | Prevent voice invitation loops |
| `patches/webrtcvoice-track-active-conference-sessions.patch` | Track per-avatar active voice sessions |
| `patches/wolfconference-voice-suppress-active-invites.patch` | Suppress false leave/rejoin notices |
| `patches/wolfconference-system-sender-opensimulator.patch` | Brand system notifications **OpenSimulator**, not Wolf Territories |
| `web/wolf_api.php` | HMAC-authenticated conference registry HTTP API |
| `web/wolf_config.php.example` | Server config template (never commit real keys/passwords) |
| `database/wolf_schema.sql` | Current MySQL schema |
| `.github/workflows/check.yml` | PHP/MySQL tests and pinned .NET 8 build |

**Legacy prototype files** `database/schema.sql`, `web/conferences.php`, and `web/config.php.example` are obsolete; **do not deploy** them.

## Security and operational limits

**Important: trusted-simulator model only.** The existing WolfVoice credential key is reused for conference HMAC requests and internal-grid IM envelopes. Any operator holding this shared secret can potentially impersonate conference actors; the allowlist checks source IP but **does not cryptographically prove the avatar identity**. It is **not safe to distribute that key to arbitrary/untrusted operators**.

The API requires HTTPS, signed request metadata, a valid allowed simulator IP (via `wolfvoice_admin.servers` or the local diagnostics list), and an isolated PHP runtime. Never commit credentials. Protect the central server, back up its database, and review the trust model before broad federation.

The supplied PHP backend is tailored to BloodMoon's WolfVoice allowlist database and **requires adaptation for an independent deployment**. Viewers, networking, security settings and other grid modules can affect compatibility. Full external deployment and third-party security auditing remain outside this release's validation.

## Build from source

The [GitHub Actions workflow](.github/workflows/check.yml) checks out `Vlad-BloodMoon/opensim` at **`v0.9.3.1-webrtc.2`**, applies **the seven original fixes and the additional system-message sender patch in order**, runs `./runprebuild.sh`, compiles `OpenSim.sln` with `dotnet build --configuration Release`, and uploads the DLL and symbols. The workflow also executes PHP syntax, HMAC vectors and disposable MySQL lifecycle tests.

Originally field-tested baseline: [run #30](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/actions/runs/42395627678). **Current same-name release asset:** corrected maintenance DLL from [successful run #40](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/actions/runs/42413642157), with generic **OpenSimulator** notification sender. The PHP API also now supports re-inviting members marked `invited`, but **first-reconnect recovery still needs in-world confirmation**. See [release notes](RELEASE_NOTES.md).

## Credits

- **WolfVoice / Lone Wolf**, WolfGrid: original WolfVoice integration and valuable work on OpenSimulator WebRTC voice.
- **OpenSimulator** community and Firestorm viewer contributors.
- **BloodMoon**: deployment, integration, testing and multi-simulator validation.

This repository is an **independent integration project**; it is not a replacement for the upstream WolfVoice server or the OpenSimulator source repository.
