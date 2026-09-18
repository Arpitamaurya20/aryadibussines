# Live operations wallboard (no login)

For office TV / live display. No admin session required.

## Open on screen

```
/admin/ticket-analytics/wallboard/live-screen.php
```

Local example:

```
http://localhost/projects/techxpert/admin/ticket-analytics/wallboard/live-screen.php
```

## Behaviour

- Shows **today’s** ticket activity (all types: R&M, PPM, Projects, Supply, AMC Breakdown)
- Refreshes data every **5 minutes** (configurable)
- Countdown to next refresh on screen
- Dark full-screen layout

## Optional security (recommended on internet)

Edit `inc/wallboard_config.php`:

```php
const WALLBOARD_SECRET = 'your-long-random-string-here';
```

Then open:

```
live-screen.php?key=your-long-random-string-here
```

Leave `WALLBOARD_SECRET` empty for open access (internal LAN only).

## Config

| Constant | Default | Purpose |
|----------|---------|---------|
| `WALLBOARD_REFRESH_SECONDS` | 300 | Auto-refresh interval |
| `WALLBOARD_TIMEZONE` | Asia/Kolkata | “Today” date boundary |
| `WALLBOARD_COMPANY_NAME` | TechXpert | Header title |
