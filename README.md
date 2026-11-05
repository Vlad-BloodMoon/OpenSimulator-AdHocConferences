# OpenSimulator Ad-Hoc Conferences

**Experimental OpenSimulator 0.9.3.1 / .NET 8 integration** for Firestorm ad-hoc conferences (temporary multi-avatar IM conversations across region processes), with WolfVoice conference rooms. **Not yet validated end-to-end in Firestorm. Do not deploy to production simulators.**

The project follows Lone Wolf's description of `WolfConference.cs` in `WebRtcVoiceRegionModule`: viewer `ChatSessionRequest`, `ChatterBoxSessionStartReply`, dialog 17 for conference text, internal signed grid-IM envelopes (dialog 2), and WebRTC rooms named `conf-<session>`. The patched module reuses the **existing** `[WebRtcVoice] CredKeyFile`; no additional conference signing key is created.

## Current components

| File | Purpose |
| --- | --- |
| `database/wolf_schema.sql` | The **active** MySQL schema: `wolf_conf_sessions` and `wolf_conf_members` |
| `web/wolf_api.php` | HMAC-authenticated central conference registry |
| `web/wolf_config.php.example` | Private configuration template, **never commit real credentials** |
| `patches/wolfconference-voice-key-and-root.patch` | Existing WolfVoice key fallback and root-only internal IM routing |
| `.github/workflows/check.yml` | PHP syntax, PHP/MySQL integration, HMAC and .NET 8 OpenSim Release build |

Older `database/schema.sql`, `web/conferences.php`, and `web/config.php.example` are **obsolete experiments**. Do not deploy them.

### Authorization

The PHP API checks an HMAC-SHA256 request signature built from the existing WolfVoice credential file, the simulator/region UUID, agent UUID, timestamp (5-minute window) and raw JSON body. It also checks the **actual TCP peer IP** (`REMOTE_ADDR`, **not** `X-Forwarded-For`) against `wolfvoice_admin.servers`: a record must have `status='allowed'` and matching `resolved_ip` or literal `server_address`. Both automatically discovered (`source='auto'`) and manually added (`source='manual'`) records work; `pending` and `blocked` do not. Only explicit local diagnostic IPs in `allowed_ips` bypass this database lookup.

**Security limitation:** Administrators who possess the shared voice key can generate valid signatures. An allowed simulator is not independently proof of an avatar's identity; HMAC and IP checks alone do not make this ready for untrusted external simulator operators. The PHP process can read the voice key; isolate it from ordinary web applications. Review the identity/trust model before wide release.

## BloodMoon installation progress — 5 November 2026

The following was **performed and checked manually** on `robust-vm`, Ubuntu 24.04:

1. Created MySQL database `opensim_adhoc` (`utf8mb4_general_ci`) and imported **`database/wolf_schema.sql`**. Both `wolf_conf_sessions` and `wolf_conf_members` exist.
2. Created local MySQL account `adhoc_user@localhost`; granted only `SELECT, INSERT, UPDATE, DELETE` on `opensim_adhoc` and **only `SELECT`** on `wolfvoice_admin.servers`. MySQL connectivity and read of **12 allowed servers** verified.
3. Reused `/etc/wolfvoice/cred_key` (owner `root:wolfvoice`, mode `640`). No new voice key was created.
4. Installed PHP 8.3 FPM while keeping the existing Apache `mod_php` serving other sites. Created Linux account `adhocconf` (primary group `adhocconf`, supplementary `wolfvoice`); confirmed it can read the existing voice key.
5. Added dedicated PHP-FPM pool at `/etc/php/8.3/fpm/pool.d/adhocconf.conf`:
   ```ini
   [adhocconf]
   user = adhocconf
   group = adhocconf
   listen = /run/php/php8.3-fpm-adhocconf.sock
   listen.owner = www-data
   listen.group = www-data
   listen.mode = 0660
   pm = ondemand
   pm.max_children = 3
   pm.process_idle_timeout = 30s
   pm.max_requests = 500
   php_admin_flag[log_errors] = on
   php_admin_value[error_log] = /var/log/php8.3-fpm.log
   php_admin_value[expose_php] = Off
   ```
   `php-fpm8.3 -t` passed; Unix socket verified with `www-data:www-data` ownership and `660` mode.
6. Enabled Apache `proxy_fcgi` (existing `proxy` was already enabled). Site document root is `/var/www/BloodMoon`, HTTPS vhost `/etc/apache2/sites-available/000-default-le-ssl.conf`. Backed up the latter to `000-default-le-ssl.conf.bak-20261105` before modification.
7. Created `/var/www/BloodMoon/adhocconf/`; deployed current `web/wolf_api.php`. Config `wolf_config.php` is owned by `louna49400:adhocconf` with `640` permissions so Notepad++/SFTP editing remains possible, the dedicated FPM user can read it, and Apache's ordinary `www-data` cannot. **The real config and database password are only on the server, not on GitHub.**
8. Added to the SSL virtual host:
   ```apache
   <Directory "/var/www/BloodMoon/adhocconf">
       Options -Indexes -MultiViews
       AllowOverride None
       Require all denied
       <Files "wolf_api.php">
           Require all granted
           SetHandler "proxy:unix:/run/php/php8.3-fpm-adhocconf.sock|fcgi://localhost/"
       </Files>
   </Directory>
   ```
   `apache2ctl configtest` returned `Syntax OK`; Apache was reloaded.
9. Confirmed externally: `GET /adhocconf/wolf_config.php` → **403**; `GET /adhocconf/wolf_api.php` → **405**, JSON `{"success":false,"error":"POST required"}`. `php -l` passed. Database access and existing voice key readability were verified with the `adhocconf` account.

Example config values (never paste a real password into GitHub):
```php
'dsn' => 'mysql:host=127.0.0.1;dbname=opensim_adhoc;charset=utf8mb4',
'db_user' => 'adhoc_user',
'db_password' => 'YOUR_PRIVATE_PASSWORD',
'existing_cred_key_file' => '/etc/wolfvoice/cred_key',
'allowed_ips' => ['127.0.0.1'], // local diagnostics only; remote IPs come from wolfvoice_admin.servers
```

### Still not tested or finished

- No signed conference lifecycle request has been exercised against **the live** BloodMoon API yet. PHP/MySQL CI uses its own disposable database.
- No Firestorm test on a real OpenSimulator simulator yet (planned: isolated **DisplayName** test simulator).
- Text chat across simulator processes, invites, join/leave/relogin, viewer voice ring and conference audio are **unverified**.
- The compiled test DLL is a replacement for `WebRtcVoiceRegionModule.dll`, **not** a complete standalone add-in. Preserve the existing voice files and make a backup before any test.
- To enable the patch in a simulator, `[WebRtcVoice] ConferenceApiUrl` must point to `https://bloodmoonpack.com/adhocconf/wolf_api.php`. The existing `CredKeyFile` remains configured, and no `[Wolf] SimSecretPath` is needed. **Do this only on the selected test simulator**, not on production engines.
- A long-lived release and safer independent simulator/actor authentication remain future work. The current shared key must never be posted or checked into git.

## Test DLL and CI

The Actions workflow builds OpenSimulator .NET 8 Release with the patch applied and uploads **`WebRtcVoiceRegionModule-adhoc-TEST-0.9.3.1`** as an artifact, containing the experimental DLL.

- [Latest verified successful workflow (5 Nov 2026)](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/actions/runs/42046505590)
- Open the workflow page, scroll to **Artifacts**, and download `WebRtcVoiceRegionModule-adhoc-TEST-0.9.3.1` (GitHub login may be required).
- That specific artifact expires **12 November 2026**; it can be rebuilt by rerunning the workflow. Compilation success is **not** a working Firestorm test.

## Résumé français

Conférences IM éphémères entre avatars, y compris sur des processus OpenSimulator différents, avec voix WebRTC à tester ensuite. Le service central PHP/MySQL est installé sur BloodMoon et vérifié pour les accès de base, HTTPS et permissions. Les IP autorisées sont reprises de `wolfvoice_admin.servers`. **Le module Firestorm/OpenSimulator n'a pas encore été testé en conditions réelles.**

Documentation de référence : [développer des add-ins OpenSimulator](http://opensimulator.org/wiki/Developing_OpenSim_Addins/fr).
