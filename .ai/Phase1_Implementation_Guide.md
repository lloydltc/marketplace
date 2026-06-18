PHASE 1: PROJECT FOUNDATION

Docker-First Laravel Marketplace

Step-by-Step Implementation Guide  |  Windows + Docker Desktop

Overview

This guide walks you through Phase 1 of the marketplace project from scratch on Windows with Docker Desktop already installed. Every service — the Laravel app, Nginx, PostgreSQL, Redis, Vite dev server, and Mailpit — runs as a Docker container. You will never install PHP, Node, or Composer directly on Windows.

Step 0 — Prerequisites

Ensure the following are present before starting:

Docker Desktop is running (you already have this)

Windows Terminal or PowerShell is available

A project folder location decided — e.g. C:\Users\HomePC\Documents\projects\marketplace

Step 1 — Create the Project Folder

Open PowerShell or Windows Terminal and run:

mkdir C:\Users\HomePC\Documents\projects\marketplace

cd C:\Users\HomePC\Documents\projects\marketplace

mkdir src

mkdir logs\nginx

mkdir docker\nginx

mkdir docker\php

Your top-level structure will be:

marketplace\

docker\

Dockerfile

nginx\

default.conf

php\

php.ini

opcache.ini

supervisord.conf

src\               <-- Laravel app lives here

logs\nginx\

docker-compose.yml

.env

Step 2 — Place the Docker Configuration Files

Copy all the provided files into your project folder exactly as shown in the folder tree above:

docker/Dockerfile

docker/nginx/default.conf

docker/php/php.ini

docker/php/opcache.ini

docker/php/supervisord.conf

docker-compose.yml

Also create the root .env file (copy from src.env provided) and place it at:  C:\Users\HomePC\Documents\projects\marketplace\.env

Step 3 — Build the Docker Image

From C:\Users\HomePC\Documents\projects\marketplace, run:

docker-compose build --no-cache

This builds the marketplace-app image with PHP 8.3, all extensions, Composer, and Supervisor. The first build takes 3-5 minutes. Subsequent builds are faster due to layer caching.

Step 4 — Create the Laravel Project

Run a one-off Composer container to scaffold Laravel inside the src\ folder:

docker run --rm -v "C:\Users\HomePC\Documents\projects\marketplace\src:/app" composer:latest ^

create-project laravel/laravel . --prefer-dist

On Linux-style terminals (Git Bash) replace the ^ line continuation with \. After this completes, src\ contains the full Laravel 12 application.

Step 5 — Configure Laravel .env

Copy the .env.example to .env inside src\ and update the relevant sections:

copy src\.env.example src\.env

Then open src\.env and update these values:

APP_URL=http://localhost:8080

DB_CONNECTION=pgsql

DB_HOST=postgres

DB_PORT=5432

DB_DATABASE=marketplace

DB_USERNAME=marketplace_user

DB_PASSWORD=secret

REDIS_CLIENT=phpredis

REDIS_HOST=redis

REDIS_PASSWORD=redissecret

REDIS_PORT=6379

CACHE_STORE=redis

QUEUE_CONNECTION=redis

SESSION_DRIVER=redis

MAIL_MAILER=smtp

MAIL_HOST=mailpit

MAIL_PORT=1025

Step 6 — Install Livewire, Alpine.js, and Tailwind

Run the following commands using the app container. First start it:

docker-compose up -d postgres redis

docker-compose run --rm app composer require livewire/livewire

Now install Node/frontend dependencies inside src\ using the Vite container:

docker run --rm -v "C:\Users\HomePC\Documents\projects\marketplace\src:/app" -w /app node:20-alpine ^

npm install

docker run --rm -v "C:\Users\HomePC\Documents\projects\marketplace\src:/app" -w /app node:20-alpine ^

npm install -D tailwindcss @tailwindcss/vite postcss autoprefixer

docker run --rm -v "C:\Users\HomePC\Documents\projects\marketplace\src:/app" -w /app node:20-alpine ^

npm install alpinejs

docker run --rm -v "C:\Users\HomePC\Documents\projects\marketplace\src:/app" -w /app node:20-alpine ^

npm install laravel-vite-plugin

Step 7 — Configure Tailwind CSS (v4 / Vite)

Tailwind v4 uses the @tailwindcss/vite plugin. Update vite.config.js in src\ (replace its contents):

import { defineConfig } from 'vite';

import laravel from 'laravel-vite-plugin';

import tailwindcss from '@tailwindcss/vite';

export default defineConfig({

plugins: [

tailwindcss(),

laravel({

input: ['resources/css/app.css', 'resources/js/app.js'],

refresh: ['resources/views/**', 'app/Livewire/**'],

}),

],

server: {

host: '0.0.0.0',

port: 5173,

hmr: { host: 'localhost' },

},

});

Update resources/css/app.css to:

@import 'tailwindcss';

Update resources/js/app.js to:

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

Step 8 — Generate App Key & Run Migrations

docker-compose run --rm app php artisan key:generate

docker-compose run --rm app php artisan migrate

If artisan migrate fails, ensure postgres is healthy first:

docker-compose up -d postgres

# Wait ~10 seconds then retry

docker-compose run --rm app php artisan migrate

Step 9 — Publish Livewire Assets

docker-compose run --rm app php artisan livewire:publish --config

Then update your main Blade layout (resources/views/layouts/app.blade.php or welcome.blade.php) to include the Livewire scripts and Vite assets:

<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>{{ config('app.name') }}</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])

@livewireStyles

</head>

<body>

{{ $slot }}

@livewireScripts

</body>

</html>

Step 10 — Start All Services

docker-compose up -d

This starts all 6 containers. Check status:

docker-compose ps

All containers should show status "running". Access the services:

Step 11 — Validate Everything Works

Run these checks in order:

# 1. Check all containers are up

docker-compose ps

# 2. Check Laravel can reach the database

docker-compose exec app php artisan tinker --execute="DB::select('select 1')"

# 3. Check Redis connection

docker-compose exec app php artisan tinker --execute="Cache::put('test','ok',10); echo Cache::get('test');"

# 4. Run default tests (should show 2 passing)

docker-compose exec app php artisan test

# 5. Check Livewire is installed

docker-compose exec app php artisan livewire:list

# 6. Open browser

start http://localhost:8080

Daily Development Workflow

Starting work

cd C:\Users\HomePC\Documents\projects\marketplace

docker-compose up -d

Running Artisan commands

docker-compose exec app php artisan make:livewire Counter

docker-compose exec app php artisan make:model Product -m

docker-compose exec app php artisan migrate

docker-compose exec app php artisan db:seed

Viewing logs

docker-compose logs -f app       # PHP / Laravel logs

docker-compose logs -f nginx      # Nginx access/error logs

docker-compose logs -f vite       # Vite build output

Stopping work

docker-compose stop

Rebuilding after Dockerfile changes

docker-compose build app --no-cache

docker-compose up -d

Troubleshooting

Port already in use

If port 8080, 5433, or 6380 is taken, edit docker-compose.yml and change the host-side port (the number before the colon).

Vite assets not loading

Ensure the vite container is running (docker-compose ps). If it shows as exited, check logs: docker-compose logs vite. Usually caused by a missing npm package.

Database connection refused

The postgres healthcheck must pass before app starts. Run docker-compose ps and ensure marketplace-postgres is "healthy" not just "running".

Permission errors on Windows

Windows volume mounts can cause file ownership issues. If you see permission denied on storage\ or bootstrap\cache\, run:

docker-compose exec app chmod -R 775 storage bootstrap/cache

docker-compose exec app chown -R www:www storage bootstrap/cache

Updated Phase 1 Implementation Checklist

Use this as your progress tracker. All tasks are performed inside Docker — no local PHP, Node, or Composer needed.

1.1  Project & Folder Scaffolding

Create C:\Users\HomePC\Documents\projects\marketplace with src\, docker\nginx, docker\php, logs\nginx

Copy all Docker config files into place

Create root .env from the provided src.env template

1.2  Docker Image Build

Run: docker-compose build --no-cache

Confirm marketplace-app image appears in Docker Desktop Images tab

1.3  Laravel Project Creation

Run Composer create-project using a one-off container

Confirm src\ contains artisan, composer.json, routes\, etc.

1.4  Environment Configuration

Copy src\.env.example to src\.env

Set DB_*, REDIS_*, CACHE_*, QUEUE_*, MAIL_* values as specified

Generate APP_KEY: docker-compose run --rm app php artisan key:generate

1.5  Frontend Stack Installation

Install Livewire: docker-compose run --rm app composer require livewire/livewire

Install Tailwind CSS v4, Alpine.js, laravel-vite-plugin via Node container

Update vite.config.js with Tailwind plugin and Docker server config

Update resources/css/app.css with @import tailwindcss

Update resources/js/app.js to initialise Alpine

1.6  Database

Run: docker-compose run --rm app php artisan migrate

Confirm default Laravel tables created (users, password_resets, etc.)

1.7  Livewire Setup

Publish Livewire config: php artisan livewire:publish --config

Update Blade layout to include @vite, @livewireStyles, @livewireScripts

1.8  Full Stack Start & Validation

Run: docker-compose up -d

All 6 containers running: app, nginx, vite, postgres, redis, mailpit

http://localhost:8080 shows Laravel welcome page

php artisan test passes

DB and Redis connections verified via tinker

Skipped (no GitHub Actions yet)

CI/CD pipeline — deferred to a later phase

Pre-commit hooks — optional, can add later

PHPStan / PHP_CodeSniffer — add in Phase 2 or 3