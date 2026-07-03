# Demo with FrankenPHP

The bundle includes a **Symfony 8** demo under `demo/symfony8/` using **FrankenPHP** and Docker Compose.

## Quick start

From the bundle root:

```bash
make -C demo/symfony8 up
```

Default URL: **http://localhost:8021** (override with `PORT` in `demo/symfony8/.env`).

The app redirects `/` to `/en/`.

## Demo pages

| Route | Description |
|-------|-------------|
| `/en/` | Home with links to examples |
| `/en/demo/level` | `policy_mode: level` (weak / medium / strong) |
| `/en/demo/conditions` | Inline `conditions` |
| `/en/demo/plain` | Plain Symfony `PasswordType` (no strength UI) |
| `/es/...` | Spanish locale |

## Commands

```bash
make -C demo/symfony8 up          # start (install + cache + assets)
make -C demo/symfony8 down        # stop
make -C demo/symfony8 shell       # PHP container shell
make -C demo/symfony8 test        # smoke checks
make -C demo/symfony8 link-bundle # use local bundle path (see demo/README.md)
```

From bundle root:

```bash
make -C demo up    # same as demo/symfony8
```

## Worker mode (optional)

FrankenPHP worker mode can be enabled in the demo `Caddyfile` for performance testing. The demo runs in standard mode by default for simpler debugging.

## Troubleshooting

- **Port in use:** set `PORT=8011` (or another free port) in `demo/symfony8/.env`.
- **Stale assets:** `make -C demo/symfony8 cache-clear` and rebuild bundle assets with `make assets` at bundle root.
- **Password toggle:** install `nowo-tech/password-toggle-bundle` in the demo to test toggle integration.
