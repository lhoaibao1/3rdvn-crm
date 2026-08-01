# UAT Development and Production Publishing Rules

- `/var/www/3rdvn-crm-uat` is the only workspace allowed for CRM source changes, migrations, builds, tests, debugging, and commits.
- `/var/www/3rdvn-crm` is production and must be treated as read-only.
- Never copy, merge, deploy, migrate, clear cache, rebuild assets, or restart production services unless the user explicitly requests **xuất bản/deploy production** in the current message.
- All normal implementation must use the UAT database `crm_laravel_uat` and UAT services only.
- Before any production publication: verify the UAT commit is clean, tests pass, database backup exists, and report the exact commit to be published.
- Production publication must promote a reviewed UAT commit; never edit production files directly.
