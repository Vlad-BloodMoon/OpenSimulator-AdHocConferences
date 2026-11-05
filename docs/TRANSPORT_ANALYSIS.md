# Conference transport notes (OpenSimulator 0.9.3.x)

## Verified source path

The current `IMessageTransferModule.SendInstantMessage` interface already accepts an optional `ToRootOnly` Boolean. The standard `MessageTransferModule` implementation searches local root presences first, otherwise (with the default false) may send directly to a **child** presence; when `ToRootOnly=true`, it forwards to grid delivery instead. Its receiving XML-RPC handler triggers `OnIncomingInstantMessage` on the local **root** presence.

The existing `WolfConference.cs` uses IM dialog 2 as an internal signed envelope; this dialog must not be delivered directly to a viewer. The included `patches/wolfconference-root-routing.patch` changes that one outgoing call to pass `true` for `ToRootOnly`. This may eliminate the need for the *specific* core change the upstream author described in their deployment, **but has not been compiled or tested end-to-end**.

## Remaining work before release

- Verify the patched call against the exact OpenSimulator 0.9.3.1 DLL interface and deployed transfer module.
- Test a sender and recipient on distinct simulator **processes**, especially during teleport (root/child transitions).
- Test an incoming `wolfconf1\n` envelope, invitation and text message without duplicate or lost deliveries.
- Integrate a session registry; the existing prototype PHP contract is **not** compatible with WolfConference's JSON responses or actions yet.
- Resolve authenticated simulator/actor identity before opening the registry to independent simulator operators. An IP allowlist is a temporary private-network restriction, not avatar authentication.
- Test Firestorm session creation, invite, join, typing-to-accept, leave, relogin, and timeout.
- Only then evaluate conference voice rooms.

## Security boundary

WolfConference currently uses a per-simulator signing secret to authenticate grid IM envelopes and service calls. Removing those HMAC checks without an equivalent trust mechanism is unsafe. A solution with **no new distributed secret** requires reusing an existing authenticated grid identity or a differently secured trusted communication channel; this remains open.
