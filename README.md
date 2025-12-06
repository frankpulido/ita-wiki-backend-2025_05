# Contributors

ORIGINAL SOURCE : https://github.com/IT-Academy-BCN/ita-wiki-backend

- Luis Vicente
- Jordi Morillo
- Juan Valdivia
- Raquel Martínez
- Stéphane Carteaux
- Diego Chacón
- Óscar Anguera
- Rossana Liendo
- Constanza Gómez
- Xavier R
- Sergio López
- Frank Pulido (@frankpulido)

# Instructions below allow you to develop locally in your computer. [<Click me to deploy to Railway>](https://www.notion.so/Set-up-Laravel-to-Deploy-to-Railway-2834893773ea8067ac07ee6fd8567813?pvs=21)
# IMPORTANT !!! Railway has deprecated NIXPACK, the actual default Builder is RAILPACK. This README needs an update.

## 1.A Prepare your project folder and files

- Create project root folder `my-project`.
- Place your Laravel app inside `my-project/laravel` with `artisan`, `composer.json`, `public`, etc.
- Place your React app inside `my-project/react`.

## 1.B If you are cloning someone else’s GitHub repository :

Just open terminal from the directory where you want to place the project root folder

```bash
git clone <project ssh root>
```

After you have performed either 1A or 1B :

- Copy environment settings template:

```bash
cp laravel/.env.example laravel/.env
```

---

## 2. Configure Docker files

- `docker-compose.yml` in project root `my-project`, defining:
    - `mysql` service (MySQL 8.0 image, port 3306 inside, mapped to host port 3700).
    - `php` service (PHP-FPM, for serving Laravel via Nginx).
    - `nginx` service (serves Laravel public dir, proxy to PHP).
    - `laravel` service (CLI container built from Laravel folder, runs composer, artisan commands, queue worker).
    - `react` service (runs React dev server on port 8989).
- Dockerfiles for:
    - PHP services (install PHP, extensions).
    - Laravel CLI container (also installs Composer).

---

## 3. Configure Laravel `.env`

Set in `my-project/laravel/.env`:

```bash
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=app
```

These match your MySQL Docker Compose environment variables at `my-project/docker-compose.yml.`

---

## 4. Start Docker containers

From the project root `my-project` folder, run:

```bash
docker-compose up -d --build
```

This builds images (if needed) and starts all containers.

---

## 5. Verify containers are running

Use:

```bash
docker-compose ps

# You will see the following (similar) :
frankpulidoalvarez@Franks-Mac-mini laravel % docker-compose ps
NAME               IMAGE              COMMAND                  SERVICE   CREATED              STATUS                        PORTS
my-project-laravel   my-project-laravel   "docker-php-entrypoi…"   laravel   About a minute ago   Up About a minute             8000/tcp
my-project-mysql     mysql:8.0          "docker-entrypoint.s…"   mysql     About a minute ago   Up About a minute (healthy)   0.0.0.0:3700->3306/tcp, [::]:3700->3306/tcp
my-project-nginx     nginx:alpine       "/docker-entrypoint.…"   nginx     About a minute ago   Up About a minute             0.0.0.0:8988->80/tcp, [::]:8988->80/tcp
my-project-php       my-project-php       "docker-php-entrypoi…"   php       About a minute ago   Up About a minute             9000/tcp
my-project-react     my-project-react     "docker-entrypoint.s…"   react     About a minute ago   Up About a minute             0.0.0.0:8989->5173/tcp, [::]:8989->5173/tcp
```

Each container (`mysql`, `nginx`, `php`, `laravel`, `react`) should show as "Up" or "Healthy".

---

## 6. Run Laravel commands inside the `laravel` container

Run in `my-project` folder :

```bash
docker-compose exec laravel composer install
docker-compose exec laravel php artisan key:generate
docker-compose exec laravel php artisan migrate
```

This (1) installs dependencies, (2) sets APP_KEY, (3) creates database tables.

---

## 7. Access your apps

- Laravel web app (served by Nginx) at:
    
    [http://localhost:8988](http://localhost:8988/)
    
- React dev server app at:
    
    [http://localhost:8989](http://localhost:8989/)
    
- MySQL database port on host at 3700 (for MySQL clients, NOT a web page).

| **URL** | **Works ?** | **Notes** |
| --- | --- | --- |
| http://localhost:8988/ | Yes | Laravel app via nginx |
| http://localhost:8989/ | Yes | React Vite dev server |
| http://localhost:3700/ | No | MySQL port (not HTTP, no web) |

```bash
# To open mysql in terminal :
docker-compose exec mysql mysql -uapp -papp app

# Then execute sql commands :
mysql> SHOW TABLES;
+-----------------------+
| Tables_in_app         |
+-----------------------+
| cache                 |
| cache_locks           |
| failed_jobs           |
| job_batches           |
| jobs                  |
| migrations            |
| password_reset_tokens |
| sessions              |
| users                 |
+-----------------------+
9 rows in set (0.00 sec)
```

---

## 8. Optional troubleshooting

- If MySQL 8 authentication errors (`caching_sha2_password`), fix by adding this to your MySQL service in `docker-compose.yml`:

```bash
command: --default-authentication-plugin=mysql_native_password
```

- Then rebuild and restart containers.
- Ensure environment variables match between `.env` and Docker Compose.
- Check logs with:

```bash
docker-compose logs laravel
docker-compose logs mysql
```

This is the full, methodical sequence to set up your Laravel app with Docker, MySQL, and React.

---

## IMPORTANT : Everytime `.env` is modified

If you modify `.env`, run these commands to clear cached configs (inside laravel container):

```bash
docker-compose exec laravel php artisan config:clear
docker-compose exec laravel php artisan cache:clear
# Or just this command below :
docker-compose exec laravel php artisan optimize:clear

# Then rebuild and restart the containers :
docker-compose down
docker-compose up -d --build

# Run migrations :
docker-compose exec laravel php artisan migrate
```

## Notes

- Use `docker-compose exec laravel php` prefix to run PHP artisan commands inside Laravel container from the project directory : bash notifier>.

```bash
frankpulidoalvarez@Franks-Mac-mini my-project % docker-compose exec laravel php artisan migrate
```

- Use `docker-compose exec php` prefix to run PHP artisan commands inside Laravel container from laravel directory : bash laravel>.

```bash
frankpulidoalvarez@Franks-Mac-mini laravel % docker-compose exec php artisan migrate
```

- `docker-compose.yml` at the root orchestrates services: Laravel app, Nginx, DB, React, PHP.
- `nginx/default.conf` configures web server behavior.
- `php/Dockerfile` defines your PHP container environment.
- Avoid running `php artisan` or `composer` commands on host directly for Dockerized app.

## DOCKERFILE

There are 2 Dockerfiles in the project **my-project :**

```bash
/my-project/laravel/Dockerfile
/my-project/php/Dockerfile
```

Your two Dockerfiles serve different purposes:

1. `my-project/php/Dockerfile` is your main PHP-FPM container for running the Laravel app web server.
2. `my-project/laravel/Dockerfile` is a separate CLI-focused image with Composer installed, used mainly to run artisan tasks and install dependencies.

## How this affects your workflow:

- The PHP container built from `my-project/php/Dockerfile` **does not include Composer** by default (which is why running `docker-compose exec php composer` gave a not found error).
- The Laravel CLI container built from `my-project/laravel/Dockerfile` **does include Composer**.
- Your `docker-compose.yml` (likely) has **both services** defined, with one using `php/Dockerfile` and one using `laravel/Dockerfile` as builds.

## What you likely need to do:

- Run Composer and Artisan commands in the **Laravel CLI container**, not the PHP-FPM container (see previous section Notes)
- Use the service name for the Laravel CLI service from your `docker-compose.yml`. In our case is `laravel`.

**How to check service names (from your project root) :**

```bash
# Run :
docker-compose ps
# We get :
frankpulidoalvarez@Franks-Mac-mini my-project % docker compose ps
NAME               IMAGE                  COMMAND                 SERVICE   CREATED       STATUS                 PORTS
my-project-laravel   my-project-laravel   "docker-php-entrypoi…". laravel   5 hours ago   Up 5 hours             8000/tcp
my-project-mysql     mysql:8.0            "docker-entrypoint.s…"  mysql     5 hours ago   Up 5 hours (healthy)   0.0.0.0:3700->3306/tcp, [::]:3700->3306/tcp
my-project-nginx     nginx:alpine         "/docker-entrypoint.…". nginx     5 hours ago   Up 5 hours             0.0.0.0:8988->80/tcp, [::]:8988->80/tcp
my-project-php       my-project-php       "docker-php-entrypoi…". php       5 hours ago   Up 5 hours             9000/tcp
my-project-react     my-project-react     "docker-entrypoint.s…". react     5 hours ago   Up 5 hours             0.0.0.0:8989->5173/tcp, [::]:8989->5173/tcp
frankpulidoalvarez@Franks-Mac-mini my-project % 

```

---

## Related

These sources collectively cover everything from the Docker configurations, environment variables management, MySQL setup inside Docker, Laravel artisan commands execution, and running migrations to access and verify your database.

### 1. DigitalOcean — How To Set Up Laravel, Nginx, and MySQL with Docker Compose

https://www.digitalocean.com/community/tutorials/how-to-set-up-laravel-nginx-and-mysql-with-docker-compose

- Detailed tutorial on building Laravel app stack using Docker Compose with PHP, MySQL, and Nginx.
- Covers service definitions, volumes, networking, environment variables, and migrations.
- Explains creating database users, running migrations, and troubleshooting.

---

### 2. YouTube - Docker + Laravel + MySQL Easy and Professional Way

https://www.youtube.com/watch?v=V-MDfE1I6u0

- Video walkthrough showing how to build Dockerized Laravel app with PHP, MySQL, and Nginx.
- Shows Dockerfile, docker-compose.yml setup, and environment config.
- Useful to see real-time setup and debugging.

---

### 3. Laravel Official Docs - Laravel Sail

https://laravel.com/docs/12.x/sail

- The official Laravel lightweight Docker development environment.
- Provides reference for how Laravel uses Docker under the hood with MySQL, Redis, and PHP.
- Good for understanding modern Docker-Laravel integration.

---

### 4. Dev.to - Dockerizing a Laravel App with Nginx, MySQL, PhpMyAdmin, and PHP

https://dev.to/kamruzzaman/dockerizing-a-laravel-app-nginx-mysql-phpmyadmin-and-php-82-43ne

- Step-by-step blog post on building a Laravel dev environment with Docker Compose.
- Includes adding PhpMyAdmin to manage MySQL visually, configuring environment variables, and running artisan commands.

---

### 5. Laracasts Discussion - Laravel through Docker Container

https://laracasts.com/discuss/channels/laravel/laravel-through-docker-container

- Active community Q&A with many practical tips, common pitfalls, and recommendations for Laravel and Docker integration.

---

### 6. Docker Docs - Laravel Development Setup

https://docs.docker.com/guides/frameworks/laravel/development-setup/

- Official Docker guide for setting Laravel development environment with Docker Compose.
- Includes PHP, MySQL, and Nginx setup, environment variable usage, volumes, and networking explained.

---