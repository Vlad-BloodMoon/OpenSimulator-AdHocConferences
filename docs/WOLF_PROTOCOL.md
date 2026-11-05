# WolfConference compatibility contract (reviewed from source)

Source examined: `OpenSim/Addons/os-webrtc-janus/WebRtcVoiceRegionModule/WolfConference.cs` (MPL 2.0; copyright IntelligentWolf Ltd.). This document is a protocol map, **not** a copy of WolfConference itself.

## Viewer / region

- Firestorm starts a temporary conference by `ChatSessionRequest`, method `start conference`; successful event-queue reply is `ChatterBoxSessionStartReply`, type **1**.
- Other methods: `invite`, `accept invitation`, `decline invitation`, `call`.
- Viewer sends conference text via IM dialog **17**, leaving via **18**. The region catches these on its **root** agent, excludes group chats, and fans them out.
- Invitations are `ChatterBoxInvitation`; membership changes are `ChatterBoxSessionAgentListUpdates`. Text by an invited user implicitly accepts.

## Shared registry request (existing WolfConference)

The region POSTs JSON including `action`, `region`, `session`; operations include `start`, `invite`, `accept`, `leave`, `members`, `relogin`, `voice`, and `wchan` (Wolf Chat rooms). `start` and `invite` include `invitees`; `start` also includes `name`. Signer supplies `X-Wolf-Sim`, `X-Wolf-Sim-Agent`, `X-Wolf-Sim-Time`, `X-Wolf-Sim-Sig` (HMAC over region, actor, timestamp and exact JSON body).

Expected success JSON includes:

```json
{
  "success": true,
  "session": { "name": "Conference with Vlad", "voice": 0 },
  "members": [ { "agent": "00000000-0000-0000-0000-000000000001", "name": "Vlad", "state": "joined" } ],
  "added": [ "00000000-0000-0000-0000-000000000002" ]
}
```

The first PHP prototype currently returns `members[{id,joined}]` and **does not match this protocol**. Do not point `ConferenceApiUrl` at it.

## Grid IM envelope

For remote recipients WolfConference uses internal IM dialog **2** with a binary bucket of `wolfconf1\\n` + HMAC signature + `\\n` + JSON. The receiver checks the recipient-bound signature and timestamp (maximum five minutes). This is distinct from ordinary private IM.

Outgoing delivery needs **root-only** routing, otherwise a child presence can consume dialog 2 and the viewer ignores it. Current OpenSimulator 0.9.3.x already exposes `IMessageTransferModule.SendInstantMessage(..., bool ToRootOnly = false)`. The supplied patch opts into `true`; compatibility and end-to-end behavior remain untested.

## Critical security question

This design's integrity relies on an authenticated sender identity. An IP-only registry is suitable at best for a tightly controlled private network; it is **not** a safe public substitute for the signing secret. No safe keyless deployment for arbitrary third-party simulators has yet been demonstrated.
