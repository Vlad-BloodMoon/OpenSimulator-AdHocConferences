# Release notes — v0.1.0 (7 November 2026)

**OpenSimulator Ad-Hoc Conferences / WolfVoice WebRTC**, first public, BloodMoon-validated build for OpenSimulator **0.9.3.1** and **.NET 8**.

## Highlights

- Multi-avatar **ad-hoc IM conferences** in Firestorm, independent of OpenSimulator groups.
- Conference invitations and text messaging across **separate simulator processes**.
- No duplicate text echo for the sender.
- Optional WebRTC voice: residents may remain in text-only chat, initiate/accept voice later, hang up individually, and rejoin without disconnecting others.
- Protection against recursive conference call invitations and false leave/rejoin notifications for already connected avatars.
- Existing WolfVoice proximity voice, private calls and ordinary group voice preserved by retaining the pinned baseline.
- Existing `CredKeyFile` reused; **no separate AdHoc secret** needed in the supported configuration.

## Validation

Tested by BloodMoon in Firestorm with multiple avatars on one and **two different** OpenSimulator simulators. CI [run #30](https://github.com/Vlad-BloodMoon/OpenSimulator-AdHocConferences/actions/runs/42395627678): .NET 8 Release build + DLL validation, PHP syntax, HMAC and MySQL lifecycle tests **passed**.

## Binary provenance

**Use the binary distributed in the GitHub Release assets.** It is packaged from the successful **run #30** artifact `WebRtcVoiceRegionModule-adhoc-NOTIFY-FIX-TEST-0.9.3.1` (artifact ID `13582834356`), compiled using [`Vlad-BloodMoon/opensim` tag `v0.9.3.1-webrtc.2`](https://github.com/Vlad-BloodMoon/opensim/releases/tag/v0.9.3.1-webrtc.2) and this repository's seven patches.

The release ZIP contains `WebRtcVoiceRegionModule.dll` and its `.pdb` symbols. **Replace the existing DLL only** on each compatible simulator; do not mix versions or replace the other WebRTC files.

## Configuration (required on *every* simulator)

```ini
[WebRtcVoice]
ConferenceApiUrl = https://bloodmoonpack.com/adhocconf/wolf_api.php
```

The BloodMoon endpoint is restricted to approved trusted simulators. Self-hosters can deploy their own PHP/MySQL backend; see [English installation](docs/INSTALLATION.md) and [French installation](docs/INSTALLATION.fr.md).

**Important:** all participating simulators need the **same compatible patched module** and **their own `ConferenceApiUrl` configuration**. Missing this option on the second simulator prevented all remote invitations in testing; adding it and restarting resolved the issue.

## Security / limitations

This release is **intended for trusted server networks**. The current conference HMAC mechanism reuses shared WolfVoice credential material; an allowed operator with the secret can impersonate conference actors. IP allowlisting is **not** independent avatar authentication. Do **not** expose the shared key or connect untrusted simulators without improving authorization. External arbitrary forks/viewers are not independently validated.

## Credits

Special thanks to **Lone Wolf (WolfGrid)** for the upstream WolfVoice work; the OpenSimulator and Firestorm communities; BloodMoon for integration and testing.
