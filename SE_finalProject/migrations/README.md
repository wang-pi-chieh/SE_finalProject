# Database Migrations

Use one SQL migration file per feature owner. Do not edit the shared `database4.sql` directly during parallel work unless the team has agreed to regenerate the baseline schema.

Recommended workflow:
1. Add schema changes in your own migration file.
2. Keep statements idempotent when possible, for example `CREATE TABLE IF NOT EXISTS`.
3. Note the feature owner and purpose at the top of the file.
