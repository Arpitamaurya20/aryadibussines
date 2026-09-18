# AryadiBusiness Portal — Database V2

This folder is a **greenfield** MySQL 8 schema. It does **not** alter, copy, or “normalize in place” the legacy database.

| File | Purpose |
|---|---|
| `ARCHITECTURE.md` | Design, lifecycles, security, ER, indexes, review |
| `01_schema.sql` | Production DDL (create independently) |
| `02_seed.sql` | Roles, permissions, mappings only (no passwords) |
| `03_migration_strategy.md` | Later mapping from legacy tables — do not run until V2 is live |

**Apply order**

1. Review `ARCHITECTURE.md`
2. Run `01_schema.sql` on a **new** database
3. Run `02_seed.sql`
4. Bootstrap the first administrator using the process in the architecture document
5. Keep the legacy database running until a controlled cutover

Do not import `admin/techxpertindia.sql` into this schema.
