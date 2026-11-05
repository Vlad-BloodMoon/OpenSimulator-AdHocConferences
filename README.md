# OpenSimulator Ad-Hoc Conferences

**Experimental add-on for OpenSimulator 0.9.3.x:** temporary, multi-avatar text IM conversations across simulator processes, compatible with the existing Firestorm conference interface. Voice integration is a later goal.

> ⚠️ **Work in progress — not installable or production-ready.** No working simulator module or end-to-end test is provided yet.

## Intended behaviour

- Create a temporary conference from Firestorm without creating a permanent group.
- Invite avatars in different OpenSimulator regions/processes.
- Deliver text using OpenSimulator's existing instant-message transport.
- Keep only temporary session membership in a separate MySQL database; do not persist chat content.
- Allow leaving the conversation, and expire inactive conference state.
- Avoid distributing a new signing key to simulator operators.

## Current contents

- `database/schema.sql`: initial MySQL tables for sessions and members.
- `web/conferences.php`: **experimental** PHP state API (start/invite/accept/leave/members).
- `web/config.php.example`: template for a **private test installation**.
- `patches/wolfconference-root-routing.patch`: one-line root-only routing change for WolfConference's signed internal envelopes.
- `docs/TRANSPORT_ANALYSIS.md`: code findings, limits, and test checklist.

## WolfConference-compatible prototype (private tests only)

A second prototype now follows WolfConference's actual request/response format:

- `database/wolf_schema.sql` — new session/member tables; use this instead of the old schema.
- `web/wolf_api.php` and `web/wolf_config.php.example` — HMAC-authenticated PHP registry.
- `patches/wolfconference-voice-key-and-root.patch` — allows WolfConference to reuse an **already configured** `[WebRtcVoice] CredKeyFile` when no `[Wolf] SimSecretPath` is set, and routes internal dialog-2 messages to root agents.

**Do not deploy as-is.** PHP syntax checks run in GitHub Actions, but there is no live MySQL integration test or C# build yet. The PHP host must possess the same existing credential key, and only authorized simulator IPs should access the endpoint. Reusing one key for both voice and conference HMAC deliberately couples their trust boundaries, so its risk needs acceptance before a production deployment. The original independent `web/conferences.php` is retained only as historical groundwork; it is **incompatible** with WolfConference.

## Automated checks

GitHub Actions checks PHP syntax, HMAC vectors, and a **PHP + MySQL integration test** of conference creation, invitation, acceptance, duplicate invitation, voice activation, leave, relogin, and rejection of a tampered signature. These checks pass on the test database, but do **not** establish Firestorm/OpenSimulator end-to-end conference functionality.

## Security and deployment status

**Do not publish the PHP endpoint on the open Internet.** Its initial access control relies on a trusted source-IP allowlist. That limits which hosts may reach it, but does **not** prove which avatar initiated each action. Therefore it is only groundwork for a restricted, trusted-network test—not a safe public service for independently operated simulators.

A verified simulator/actor trust boundary, compatibility checks against the patched WolfConference module, and full cross-process integration tests are required before release. The existing Display Names project is a reference for packaging (`.addin.xml`, separate DLL, OpenSim.ini configuration, PHP + MySQL backend), not proof that IM conferences work the same way.

## Français

Projet expérimental de **conférences IM éphémères entre plusieurs avatars**, y compris sur des simulateurs OpenSimulator distincts. Le chat écrit est prioritaire ; la voix viendra ensuite.

**Aucun module fonctionnel n'est encore livré.** Les fichiers PHP/SQL sont une première base de travail, non une version à installer en production. Le point essentiel restant est de sécuriser l'identité des simulateurs et des avatars **sans nouvelle clé à distribuer**.

## Compatibility goal

OpenSimulator 0.9.3.1 / .NET 8, Firestorm-compatible conference flows. No modifications to Firestorm are intended.
