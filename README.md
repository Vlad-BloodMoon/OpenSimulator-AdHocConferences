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

## Security and deployment status

**Do not publish the PHP endpoint on the open Internet.** Its initial access control relies on a trusted source-IP allowlist. That limits which hosts may reach it, but does **not** prove which avatar initiated each action. Therefore it is only groundwork for a restricted, trusted-network test—not a safe public service for independently operated simulators.

A proper simulator/actor trust boundary, a compatible C# add-in, and full cross-process integration are required before release. The existing Display Names project is a reference for packaging (`.addin.xml`, separate DLL, OpenSim.ini configuration, PHP + MySQL backend), not proof that IM conferences work the same way.

## Français

Projet expérimental de **conférences IM éphémères entre plusieurs avatars**, y compris sur des simulateurs OpenSimulator distincts. Le chat écrit est prioritaire ; la voix viendra ensuite.

**Aucun module fonctionnel n'est encore livré.** Les fichiers PHP/SQL sont une première base de travail, non une version à installer en production. Le point essentiel restant est de sécuriser l'identité des simulateurs et des avatars **sans nouvelle clé à distribuer**.

## Compatibility goal

OpenSimulator 0.9.3.1 / .NET 8, Firestorm-compatible conference flows. No modifications to Firestorm are intended.
