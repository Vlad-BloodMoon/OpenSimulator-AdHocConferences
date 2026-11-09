# BloodMoon production configuration branch

This branch is reserved for **BloodMoon-specific integration**. It is not the recommended starting point for a new independent grid.

- **Production mode:** `authorization_source = bloodmoon_db` reads the existing `wolfvoice_admin.servers` allowlist, with status `allowed`, plus restricted local diagnostics.
- **Voice authentication:** preserve the currently configured credential key and trusted-IP arrangement. Do not add or rotate a relay key just because the public install guide illustrates two keys.
- **Conference API:** retain the host-specific endpoint only in PRIVATE deployment configuration. No private keys, credentials, database passwords, public IP inventory or internal operational settings should be committed.
- **Standalone users:** work from the default `main` branch, use `authorization_source = local` and set `trusted_simulator_ips` for their own hosts.

The original voice server and the validated OpenSimulator module release remain separate. See the general installation at https://github.com/Vlad-BloodMoon/BloodMoon-Voice/blob/main/docs/INSTALLATION.md.

This branch is a configuration-oriented development starting point, **not an automatic snapshot of the live production server**. Check actual deployment files before deploying changes.
