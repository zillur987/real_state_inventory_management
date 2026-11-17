# Step 1 - Foundation + Authentication + Dashboard shell

Copy the folders from this zip into your Laravel 13 project root (overwrite when asked).

## 1. Install packages
```bash
php artisan install:api        # installs Sanctum, creates routes/api.php (then overwrite with ours)
npm install vue vue-router pinia axios lucide-vue-next
npm install -D @vitejs/plugin-vue
# tailwindcss, @tailwindcss/vite and laravel-vite-plugin come with the Laravel starter; if missing:
# npm install -D tailwindcss @tailwindcss/vite
```

## 2. Edit bootstrap/app.php (two small changes)
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',      // <- make sure this exists
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
->withMiddleware(function (Middleware $middleware): void {
    $middleware->statefulApi();             // <- add this
})
```

## 3. Register the provider in bootstrap/providers.php
```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,   // <- add
];
```

## 4. .env
```
APP_URL=http://localhost:8000
SESSION_DRIVER=database
SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000
```
Open the app on the Laravel URL (localhost:8000), not on the Vite dev-server URL.

## 5. Migrate, seed, run
Add `$this->call(AdminUserSeeder::class);` inside DatabaseSeeder::run(), then:
```bash
php artisan migrate
php artisan db:seed
php artisan test --filter=LoginTest
php artisan serve        # terminal 1
npm run dev              # terminal 2
```
Login: admin@example.com / password  (change later)

## Architecture rules from here on
- Controller -> Service (interface) -> Repository (interface) -> Eloquent Model
- Controllers stay thin: FormRequest validates, Service holds business logic, Repository holds queries
- All bindings live in RepositoryServiceProvider (Dependency Inversion)
- Frontend: pages -> Pinia stores -> api/*.js -> http client; home screen is driven by config/modules.js
