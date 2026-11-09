# Installation guide — OpenSimulator Ad-Hoc Conferences

> **Current complete installation:** for a new BloodMoon Voice deployment use the [BloodMoon Voice server](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/releases/tag/v1.0.0), the **three matching DLL/PDB pairs from [OpenSimulator v0.9.3.1-webrtc.3](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.3)**, and this repository's PHP/MySQL conference API. **The .3 regional DLL already contains the AdHoc fixes. Do not replace it with the older standalone AdHoc DLL.** Read the [complete installation guide](https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/COMPLETE_STACK.md). The .2 steps below are preserved for historical standalone patch builds.

This guide covers the **tested BloodMoon build** for **OpenSimulator 0.9.3.1 / .NET 8**, **Firestorm**, and the **WolfVoice** backend. The compiled DLL is not a generic binary for unrelated OpenSimulator forks.

## Requirements

- A functioning OpenSimulator 0.9.3.1 simulator using the full WolfVoice/WebRTC integration from [`v0.9.3.1-webrtc.2`](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.2).
- The existing voice modules and the existing **credential-key file** used by `[WebRtcVoice] CredKeyFile`.
- A reachable central conference API over HTTPS, a functioning OpenSimulator grid IM transfer service and suitable network routing.
- A **trusted, approved** simulator connected to the same voice/conference infrastructure. Never publish or transmit credential secrets in an issue, screenshot, or GitHub commit.

## A. Simulator installation — repeat on *every* simulator

1. **Stop** the selected simulator. Back up `bin/WebRtcVoiceRegionModule.dll` so it can be restored.
2. Download the versioned ZIP from [Releases](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/releases).
3. Extract and copy **only `WebRtcVoiceRegionModule.dll`** into this simulator's `bin/` directory. **Do not replace** its other WebRTC, economy, search or OpenSimulator DLLs.
4. Open the simulator's `wolfvoice.ini` (or the INI that contains its `[WebRtcVoice]` section) and retain existing configuration. Add:

   ```ini
   [WebRtcVoice]
   ConferenceApiUrl = https://bloodmoonpack.com/adhocconf/wolf_api.php
   ```

   The endpoint above belongs to BloodMoon. If you run your own server, use the HTTPS URL of your own deployed `wolf_api.php` instead.
5. Keep the existing `[WebRtcVoice] CredKeyFile` setting and key file. The module reuses it; **no new `[Wolf] SimSecretPath` is required** by the default configuration. Do not remove or rotate an existing key without coordinating your voice server.
6. Start the simulator. Confirm WebRTC initializes and there is **no** log stating `no ConferenceApiUrl`.
7. Create a **new multi-avatar IM conversation** in Firestorm; verify that each participant receives the invitation and text. Then initiate a **voice** call, hang up from one participant only, and rejoin.
8. **Repeat the same install and INI change on the second simulator**, then test one avatar per simulator. Each destination must run the patched module; merely installing it on the source is insufficient.

**Known working behavior (BloodMoon test, 7 Nov 2026):** text IMs delivered once, conference voice optional, individual voice hang-up/rejoin, active listeners not forced out, and cross-simulator invitation/text/voice.

## B. Central registry installation — for a *self-hosted, trusted* deployment

**BloodMoon administrators:** the current deployment already runs at `https://bloodmoonpack.com/adhocconf/wolf_api.php`; these steps describe the components for another **trusted** installation, not a command to overwrite the live environment.

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

For logs on BloodMoon's Displayname simulator:

```bash
grep -Ei '\[CONF\]|\[CALL\]|\[CHATSESSION\]|\[CONF-TEXT-DIAG\]' \
  /home/louna49400/OpenSim/Displayname/bin/OpenSim.log | tail -80
```

For other grids, substitute your own simulator path. Production logs may contain avatar UUIDs and session identifiers; redact them before posting publicly.

## D. Rebuilding

The repository's [CI workflow](../.github/workflows/check.yml) uses the pinned **`v0.9.3.1-webrtc.2`** baseline and applies **seven patches in sequence**. The generated binary is `opensim/bin/WebRtcVoiceRegionModule.dll`; it is compiled for the matching OpenSimulator distribution. The [versioned release](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/releases) is preferable to short-lived Actions artifacts.

Read [architecture and security](ARCHITECTURE.md) before deploying beyond a trusted simulator network.
