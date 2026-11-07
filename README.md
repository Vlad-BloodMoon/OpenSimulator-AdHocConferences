# OpenSimulator Ad-Hoc Conferences · WolfVoice WebRTC

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

## Downloads

Get the **versioned DLL ZIP** from [GitHub Releases](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/releases). The DLL comes from the .NET 8 build of **[`Vlad-BloodMoon/opensim` tag `v0.9.3.1-webrtc.2`](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.2)** plus the patches in this repository.

**Important:** `WebRtcVoiceRegionModule.dll` is a **replacement for the existing DLL**, **not** a standalone module. You must already have the other matching WebRTC/WolfVoice modules and the required key file(s) from the **same baseline release**. Do **not** mix it with `master`, older builds, Janus-only modules, or arbitrary OpenSimulator versions.

## Quick start — each simulator

1. Back up your current `bin/WebRtcVoiceRegionModule.dll` and stop that simulator.
2. Replace **only** `bin/WebRtcVoiceRegionModule.dll` with the DLL from the release ZIP; retain the other WolfVoice/WebRTC files.
3. In that simulator's `wolfvoice.ini` (or corresponding OpenSim configuration), add to **`[WebRtcVoice]`**:

   ```ini
   [WebRtcVoice]
   ; Preserve all existing Enabled, voice service, and key settings.
   ConferenceApiUrl = https://bloodmoonpack.com/adhocconf/wolf_api.php
   ```

4. Preserve the **existing** `CredKeyFile` setting / credential-key file; **no new `[Wolf] SimSecretPath` is needed** in the supported key-fallback configuration.
5. Restart the simulator; test a multi-avatar IM, then an optional conference call.
6. **Repeat steps 1–5 on every participating simulator**, including simulators belonging to other trusted/allowed operators. **Installing the DLL without `ConferenceApiUrl` is insufficient.**

That URL is the **BloodMoon-hosted test/service endpoint**, not a generic hosted service. Access is restricted to permitted hosts, and the service relies on the **shared existing credential secret**. Do not share the secret publicly or assume an unapproved simulator can use that endpoint. Self-hosters can deploy their own endpoint with the server files in this repository.

Full steps: [English installation guide](docs/INSTALLATION.md) · [Guide français](docs/INSTALLATION.fr.md).

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

The [GitHub Actions workflow](.github/workflows/check.yml) checks out `Vlad-BloodMoon/opensim` at **`v0.9.3.1-webrtc.2`**, applies **all seven patches in order**, runs `./runprebuild.sh`, compiles `OpenSim.sln` with `dotnet build --configuration Release`, and uploads the DLL and symbols. The workflow also executes PHP syntax, HMAC vectors and disposable MySQL lifecycle tests.

Validated binary baseline: [successful run #30](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/actions/runs/42395627678). The GitHub release asset is derived from that validated build; see [release notes](RELEASE_NOTES.md).

## Credits

- **WolfVoice / Lone Wolf**, WolfGrid: original WolfVoice integration and valuable work on OpenSimulator WebRTC voice.
- **OpenSimulator** community and Firestorm viewer contributors.
- **BloodMoon**: deployment, integration, testing and multi-simulator validation.

This repository is an **independent integration project**; it is not a replacement for the upstream WolfVoice server or the OpenSimulator source repository.
