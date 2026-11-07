# Architecture, protocol and security

## Supported scope

**Reference build:** [OpenSimulator `v0.9.3.1-webrtc.2`](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.2), .NET 8, compatible Firestorm WebRTC viewer and WolfVoice. **Validated at BloodMoon on two simulator processes (7 November 2026)**. The source patches in this repository are not an independently versioned OpenSimulator core.

## Components and responsibilities

1. **Viewer / Firestorm:** creates an ad-hoc IM session via `ChatSessionRequest`; uses `ImprovedInstantMessage` (conference dialog 17) for text; sends WebRTC voice requests only when a resident calls or joins.
2. **`WebRtcVoiceRegionModule.dll` on *each* simulator:** registers viewer session capability handlers, manages conference IM invitations/text and voice credentials. The patched source lives in the existing OpenSimulator project and builds together with OpenSimulator.
3. **OpenSimulator `MessageTransferModule`:** routes internal signed conference envelopes to remote root-agent simulators using grid IM transport. These are **internal** events (dialog 2, `wolfconf1`), not ordinary private IMs.
4. **Central `wolf_api.php` + MySQL:** creates temporary conference sessions and stores members with `invited` / `joined` state. Conference voice is signaled with the `voice` action. The backend is not a media relay.
5. **WolfVoice WebRTC server:** transports actual media in conference channels named `conf-<session UUID>`. Ordinary parcel, private and group voice retain their own channels/behavior.

## High-level flows

### Create conference and deliver invitation

- Firestorm A requests `start conference`.
- Simulator A signs an HTTPS request to `wolf_api.php` using the existing WolfVoice credential key. PHP checks timestamp, HMAC, permitted source IP and session rules, then records the conference.
- Simulator A delivers each invitation locally if the destination is a root avatar in the same simulator process.
- Otherwise, Simulator A uses `IMessageTransferModule` with the **root-only** setting and a signed `wolfconf1` envelope; Simulator B receives it with `OnIncomingConference`, validates the signature and timestamp, then delivers it to Firestorm B.
- **If Simulator B lacks `ConferenceApiUrl`**, its conference handler is not fully enabled and cross-simulator invites cannot work.

### Text messages

- The viewer sends conference dialog 17 to its simulator.
- The handler verifies session and membership via the registry and fans out to the other participants.
- The sender's viewer already shows its own message; **server self-echo is suppressed** to prevent duplicates.
- Inter-simulator recipients receive an authenticated grid-IM envelope before their viewer sees the message.
- Messages are **not** a complete, persistent public chat-history service. The handler returns an empty history array when Firestorm requests `fetch history`.

### Voice calls

- A conference **can remain text-only** until someone explicitly initiates voice.
- The server verifies room permission and returns per-avatar WolfVoice credentials.
- Invitations are rate-limited/suppressed to avoid a recursive calling cascade when invited viewers respond.
- The module tracks active viewer conference sessions and **does not send new invitations to participants already connected**. This prevents fake leave/rejoin notifications in Firestorm.
- An avatar can leave audio without leaving the text conference and can rejoin using the same conference.
- **Leaving the text conference** is different from **hanging up voice**: deleting conference membership should revoke further voice-room admission.

## Security controls

- API signatures are HMAC-SHA256 and include the region ID, agent ID, Unix timestamp, and exact raw request body; the backend accepts a limited time window.
- The API uses HTTPS and only accepts authenticated POST requests.
- Conference envelope signatures bind the payload to its recipient, with a freshness check.
- The remote-simulator IP check uses `REMOTE_ADDR` and the `wolfvoice_admin.servers` table. Only `status='allowed'` records qualify, plus explicitly listed local diagnostics IPs.
- The PHP backend should run under a dedicated, isolated account with **minimum database privileges**, and it needs restricted read access to the existing voice credential secret.
- The simulator module reuses `[WebRtcVoice] CredKeyFile`; it does not need another externally distributed conference secret.

### Critical trust caveat

**HMAC plus IP allowlisting does not authenticate the actor/avatar independently from an allowed simulator.** A simulator operator who has the shared voice credential key may sign calls claiming arbitrary agent IDs. This is a **trusted-server architecture**. Do not distribute the shared secret to untrusted third-party operators or advertise the BloodMoon endpoint as open federation. Before untrusted federation, consider separate per-simulator credentials, actor-presence attestation and independent authorization.

### Additional boundaries

- The supplied server code assumes a compatible **WolfVoice IP whitelist database** and trusted existing key material.
- Keep real config files, private passwords, voice keys, TLS private keys and service tokens **out of Git**.
- Do not log or publish raw WebRTC SDP/ICE credentials, HMAC bodies, or secret files when troubleshooting.
- The initial release is verified by local functional tests and CI, **not** a formal security review.
- GitHub release DLLs must be used with **matching WolfVoice/OpenSimulator dependencies**.

## Repository build and deployment model

The [CI build](../.github/workflows/check.yml) checks out the pinned upstream release `v0.9.3.1-webrtc.2`, applies the seven patches in their listed order, generates OpenSimulator projects with `./runprebuild.sh`, and builds the Release configuration with .NET 8. The output DLL replaces `bin/WebRtcVoiceRegionModule.dll`; it is **not** an add-in to install alongside a second copy.

The server-side deployment imports [`database/wolf_schema.sql`](../database/wolf_schema.sql), deploys [`web/wolf_api.php`](../web/wolf_api.php) and a **private copy** of `web/wolf_config.php.example`. Never deploy the earlier prototype schema/API files.

Refer to [Installation guide](INSTALLATION.md) / [Guide français](INSTALLATION.fr.md).
