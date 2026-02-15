# Database Skills & Context

## Connection Details
The application uses a standard MySQL connection via `mysqli`.
- **Hostname**: Defined by `DB_HOSTNAME` env var (default to remote if not set).
- **Credentials**: `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE` from env vars.
- **File**: `database.php` handles the connection logic.

### Usage Pattern
Always use `DBConnect()` to get a link and `DBExecute($link, $query)` to run queries.
```php
require_once 'database.php';
$link = DBConnect();
if ($link) {
    $result = DBExecute($link, "SELECT * FROM Table");
    // ...
    DBClose($link);
}
```

## Migrations
Database changes **MUST** be versioned using the migration script system.
- **Directory**: `database/migrations/`
- **Filename Format**: `YYYYMMDD_SEQ_description.sql` (e.g., `20260215_0001_add_column.sql`)
- **Execution**: The script `scripts/migrate.php` runs pending migrations.
    - It checks the `Migrations` table to see what has already been run.
    - It executes files in alphabetical order.

**IMPORTANT**:
- Never modify the schema manually on the production database. Always create a migration file.
- When creating a migration, always include a `ROLLBACK` section (commented out) or ensure changes are safe.
- `scripts/dump_schema.php` can be used to export the current schema structure.

## Common Tables
- `Clientes`: Customer data.
- `Faturas`: Invoice headers.
- `ItensFatura`: Invoice line items.
- `Servicos`: Service catalog.
- `Pagamentos`: PIX payments and transactions.
- `ConfiguracoesEmissor`: System settings (only one row).
