# Installation guide — OpenSimulator Ad-Hoc Conferences

> **Current complete installation:** for a new BloodMoon Voice deployment use the [BloodMoon Voice server](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/releases/tag/v1.0.0), the **three matching DLL/PDB pairs from [OpenSimulator v0.9.3.1-webrtc.3](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3)**, and this repository's PHP/MySQL conference API. **The .3 regional DLL already contains the AdHoc fixes. Do not replace it with the older standalone AdHoc DLL.** Read the [complete installation guide](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/COMPLETE_STACK.md). The .2 steps below are preserved for historical standalone patch builds.

This guide covers the **tested BloodMoon build** for **OpenSimulator 0.9.3.1 / .NET 8**, **Firestorm**, and the **WolfVoice** backend. The compiled DLL is not a generic binary for unrelated OpenSimulator forks.

## Recommended complete .3 installation (read this first)

For new deployments use [BloodMoon Voice v1.0.0](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/releases/tag/v1.0.0), the **full [OpenSimulator v0.9.3.1-webrtc.3 module ZIP](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3)** (three matching DLL/PDB pairs) and separately deploy **only the PHP/MySQL conference API** from this repository. The .3 package **already includes** the tested AdHoc regional DLL. **Do not install the older standalone v0.1.0 regional DLL on top of .3.** [Complete step-by-step installation](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/COMPLETE_STACK.md).

The older module-replacement procedure in section A below is **for historical .2-based development only**, not the recommended fresh .3 installation.

## Requirements

- A functioning OpenSimulator 0.9.3.1 / .NET 8 simulator with the **complete [v0.9.3.1-webrtc.3](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3)** module package.
- The existing voice modules and the existing **credential-key file** used by `[WebRtcVoice] CredKeyFile`.
- A reachable central conference API over HTTPS, a functioning OpenSimulator grid IM transfer service and suitable network routing.
- A **trusted, approved** simulator connected to the same voice/conference infrastructure. Never publish or transmit credential secrets in an issue, screenshot, or GitHub commit.

## A. Recommended simulator installation — complete .3 stack

1. Install the [BloodMoon Voice v1.0.0](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/releases/tag/v1.0.0) backend on your own voice host using the [full English guide](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/INSTALLATION.md).
2. Stop each simulator and back up its existing voice DLLs, `wolfvoice.ini` and keys.
3. Install **all three matching DLL/PDB pairs** from [OpenSimulator v0.9.3.1-webrtc.3](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3). **The AdHoc-enabled region DLL is already included**; do not overwrite it with the older `v0.1.0` standalone DLL.
4. Deploy your separate central PHP/MySQL API as described in section B.
5. Preserve existing `CredKeyFile` and other voice settings and add your own API URL to `[WebRtcVoice]` on **every participating simulator**:

```ini
ConferenceApiUrl = https://conference.example.org/adhocconf/wolf_api.php
```

**Important:** `conference.example.org` is a **fictional example domain**, not the BloodMoon service. Replace it with your own installed API's real HTTPS domain and path. BloodMoon's production endpoint is private and not open to unapproved simulators.

6. Restart each simulator. Test text invitations and messages, optional conference voice, hang-up and rejoin, then repeat between two separate simulator processes.

The historical `v0.1.0` regional-DLL-only package is for developers working with the original `.2` baseline, **not an extra module to install on top of `.3`**.

## Self-host your PHP/MySQL conference API — step by step

These steps assume **your own** Ubuntu HTTPS web host, PHP 8.3+ with PDO MySQL, and MySQL 8. They are for a new trusted deployment, **not for modifying the running BloodMoon production service**. Back up existing settings and never publish private voice keys.

### 1. Obtain the current API source

```bash
git clone https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences.git
cd OpenSimulator-AdHocConferences
```

Use `database/wolf_schema.sql`, `web/wolf_api.php` and `web/wolf_config.php.example`. Do **not** deploy the obsolete prototype files `database/schema.sql` or `web/conferences.php`.

### 2. Create your MySQL database

Run the following as a MySQL administrator, replacing the sample password:

```sql
CREATE DATABASE opensim_adhoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'adhoc_user'@'localhost' IDENTIFIED BY 'REPLACE_WITH_A_STRONG_SECRET';
GRANT SELECT, INSERT, UPDATE, DELETE ON opensim_adhoc.* TO 'adhoc_user'@'localhost';
```

Import the supplied schema:

```bash
mysql -u root -p opensim_adhoc < database/wolf_schema.sql
```

**Important dependency:** the current API queries `wolfvoice_admin.servers` for a record with `status='allowed'`, matching `resolved_ip` or `server_address`. This registry is **not created by `wolf_schema.sql`**. If your infrastructure does not already have it, create a compatible trusted-server registry and grant the API's SQL user narrowly scoped SELECT permission, **or adapt the PHP to your own allowlist**. The current backend is not fully plug-and-play without this step.

### 3. Configure your HTTPS API

Host `web/wolf_api.php` behind your own HTTPS domain. Copy `web/wolf_config.php.example` to a private `wolf_config.php` in the same directory and set the actual DSN, database credentials, **existing voice credential key file path**, and runtime limits. Restrict this configuration file from HTTP access and limit local read permissions to the PHP service account. Do not commit the key or passwords.

The PHP host needs to read the **same credential secret** as your trusted voice/simulator installation. Only permit your approved simulator hosts to connect, with an appropriate firewall and trusted-server registry. The API checks `REMOTE_ADDR`, not arbitrary forwarded headers.

### 4. Configure every OpenSimulator region

Use the complete **[OpenSimulator v0.9.3.1-webrtc.3](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3)** DLL/PDB package. The correct AdHoc regional DLL is already included: do not replace it with the old standalone download.

In each simulator's `bin/config/wolfvoice.ini`, keep your existing voice settings and add:

```ini
[WebRtcVoice]
ConferenceApiUrl = https://conference.example.org/adhocconf/wolf_api.php
```

**Replace the fictional `conference.example.org` hostname and path with your own real HTTPS PHP API location.** BloodMoon's hosted conference service is private, not an open public endpoint. Restart every participating simulator.

### 5. Validate and troubleshoot

- An HTTP **GET** request to `wolf_api.php` normally returns **405 (POST required)**: this only confirms the HTTP route is present, **not** that conferences work.
- **403:** verify allowed simulator IP, the shared credential key and request signature.
- **503:** investigate MySQL connectivity or the credential key path.
- Test in Firestorm: create a multi-avatar IM, check text delivery and optional voice, hang up/rejoin, then repeat between avatars on **two separate simulator processes**.
- If the second simulator receives no invite, check its matching `.3` DLLs, `ConferenceApiUrl`, trust registry and OpenSimulator IM routing.

**Security:** a holder of the shared credential secret may be able to sign actions on behalf of other agent UUIDs. Use only trusted simulator operators; the current design is not secure open federation with independent avatar authentication.

## C. Troubleshooting

| Symptom | Check |
| --- | --- |
| Works in one simulator but the other resident gets **no invitation** | Set `ConferenceApiUrl` on **both** simulators; restart the second; ensure its DLL is the matching version and its host is approved |
| Calls work but conference text is missing | Ensure the destination has the patched `WebRtcVoiceRegionModule.dll` and working grid IM routing; inspect `[CONF]` and `[CONF-TEXT-DIAG]` logs |
| `no ConferenceApiUrl` | The `[WebRtcVoice]` setting is absent or the simulator has not restarted with the edited INI |
| HTTP `403` from the API | Investigate host authorization, secret consistency and signature validation; never paste keys into public diagnostics |
| Voice authorization denied | Verify membership, voice key, voice permissions, and whether a participant actually left the **text** conference rather than just its audio |
| Old or mismatched DLL | Restore the backed-up DLL, verify the full `v0.9.3.1-webrtc.2` module baseline, then upgrade again |

For logs, use your own OpenSimulator log path (the following is a generic example):

```bash
grep -Ei '\[CONF\]|\[CALL\]|\[CHATSESSION\]|\[CONF-TEXT-DIAG\]' \
  /path/to/your/OpenSim/bin/OpenSim.log | tail -80
```

For other grids, substitute your own simulator path. Production logs may contain avatar UUIDs and session identifiers; redact them before posting publicly.

## D. Rebuilding

The repository's [CI workflow](../.github/workflows/check.yml) uses the pinned **`v0.9.3.1-webrtc.2`** baseline and applies **seven patches in sequence**. The generated binary is `opensim/bin/WebRtcVoiceRegionModule.dll`; it is compiled for the matching OpenSimulator distribution. The [versioned release](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/releases) is preferable to short-lived Actions artifacts.

Read [architecture and security](ARCHITECTURE.md) before deploying beyond a trusted simulator network.
