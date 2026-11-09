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

## B. Central registry installation — for a *self-hosted, trusted* deployment

**BloodMoon administrators:** the current deployment already uses a private BloodMoon conference endpoint; these steps describe the components for another **trusted** installation, not a command to overwrite the live environment.

1. Prepare a dedicated MySQL 8 database with UTF-8 support. Import **`database/wolf_schema.sql`** (not the obsolete prototype schema). This creates `wolf_conf_sessions` and `wolf_conf_members`.
2. Create a low-privilege database user with `SELECT, INSERT, UPDATE, DELETE` on the conference database. **The current `wolf_api.php` also queries `wolfvoice_admin.servers` for `status='allowed'` and the configured source IP**; grant narrowly scoped `SELECT` on that table and provide a compatible allowlist, or adapt the code to your own trusted-host registry. The PHP does **not** work unchanged without that dependency.
3. Host **`web/wolf_api.php`** on HTTPS with PHP 8.3+ and PDO MySQL. Copy **`web/wolf_config.php.example`** to `wolf_config.php` and set your database DSN, username, password, `existing_cred_key_file`, limits and diagnostic IPs. Restrict permissions so only its dedicated PHP process can read secrets.
4. Isolate the PHP-FPM pool/service and deny direct HTTP access to `wolf_config.php`. Serve **only** `wolf_api.php`. In the BloodMoon Apache/FPM deployment, GET on the config returns **403** and GET on the API returns **405** because it accepts signed POST requests.
5. Ensure PHP can read the **existing** WolfVoice credential key and that approved simulators have the compatible key. IP checks use `REMOTE_ADDR`, not untrusted proxy headers.
6. Set `ConferenceApiUrl` on **every** simulator to your HTTPS API. A normal browser visit to that URL is not a working conference test: POST bodies must be authenticated by HMAC.

### Example PHP configuration

```php
<?php
return [
    'dsn' => 'mysql:host=127.0.0.1;dbname=opensim_adhoc;charset=utf8mb4',
    'db_user' => 'adhoc_user',
    'db_password' => 'REPLACE_WITH_PRIVATE_VALUE',
    'existing_cred_key_file' => '/etc/wolfvoice/cred_key',
    'allowed_ips' => ['127.0.0.1', '::1'], // diagnostics only
    'max_members' => 25,
    'ttl_seconds' => 21600,
];
```

**Critical trust constraint:** an operator with the shared voice signing key can sign requests for different agent UUIDs. Being on the IP allowlist does **not** prove avatar identity. Do not enable untrusted third-party simulators with this shared secret. Prefer a stronger per-simulator identity design before open federation.

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
