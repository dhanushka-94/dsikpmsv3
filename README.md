# DSI KPI Monitoring System

KPI, projects, tasks, and organisational performance monitoring for **DSI Footwear**.

**Live:** [https://dsikpims.olexto.com](https://dsikpims.olexto.com)

---

## Developed by

**[olexto Digital Solutions](https://olexto.com)** — custom web applications, KPI platforms, and business systems that help teams track what matters.

| | |
|---|---|
| **Developer** | olexto Digital Solutions |
| **Email** | [info@olexto.com](mailto:info@olexto.com) |
| **Website** | [https://olexto.com](https://olexto.com) |

See **[CREDITS.md](CREDITS.md)** for full studio credits and contact details.

---

## Highlights

- KPI formulas, monthly feed history, charts, and financial-year filters
- Projects, task boards, and user progress views
- Organisation master data (companies, plants, departments, designations)
- Employee import from Excel, profile photos by EPF, role-based access
- Activity audit log and Super Admin changelog / versioning

---

## Stack

- PHP 8.2+ / Laravel 12
- MySQL
- Vite + Tailwind CSS 4

---

## Local setup

```bash
composer install
cp .env.example .env   # or use your existing .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Timezone defaults to **Asia/Colombo** (Sri Lanka).

---

## Production notes

- Use `.env.production` values on the host (do not commit secrets)
- `APP_URL=https://dsikpims.olexto.com`
- After deploy: `php artisan config:cache`, `php artisan migrate --force`, `php artisan storage:link`

Useful Artisan helpers:

```bash
php artisan users:sync-excel
php artisan users:sync-emails-from-excel
php artisan users:import-photos "path/to/photos"
php artisan software:bump patch --title="..." --notes="..."
```

---

## License & credits

Application work for DSI is developed and maintained by **olexto Digital Solutions**.  
Framework components retain their respective open-source licenses (e.g. Laravel MIT).

**Contact:** info@olexto.com · [olexto.com](https://olexto.com)
