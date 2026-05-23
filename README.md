IMPORTANT:
- Jobs table name were used already by Laravel jobs table, so why we used work_jobs table name to store created jobs data.

## Local run (Windows)

1. **PHP 8.4+** is required (Laravel 13 / Symfony 8). XAMPP’s PHP 8.2 is not enough. Use [Laravel Herd for Windows](https://herd.laravel.com/windows) or install PHP 8.4 from [windows.php.net](https://windows.php.net/download/) and put it **before** XAMPP in your `PATH`.
2. Create a MySQL database (default name in `.env.example`: `jobflowmu`) and match `DB_*` in `.env`.
3. From the project folder:

```powershell
.\scripts\dev-setup.ps1
npm run dev
```

Open **http://jobflowmu.test** in the browser (Herd serves the app; `php artisan serve` is optional).

### Git Bash (MINGW64)

Git Bash often uses **XAMPP PHP 8.2** first. You will see `require PHP >= 8.4` errors. Fix:

```bash
# Git Bash ignores Herd's php.bat and keeps XAMPP php.exe — use our helper:
source ./scripts/use-herd-php.sh
php -v   # must show 8.4.x (script defines php() -> herd/php84/php.exe)

npm install
npm run dev
```

Or run everything in one step:

```bash
bash ./scripts/dev.sh
```

**Permanent fix** — add to `~/.bashrc`:

```bash
export PATH="$HOME/.config/herd/bin:$PATH"
```

Then close Git Bash and open it again. Check: `which php` should point to `~/.config/herd/bin/php`, not `C:/xampp/php/php.exe`.

4. For AI interview chat, set `OPENAI_API_KEY` in `.env` (see `config/ai.php` for other providers).