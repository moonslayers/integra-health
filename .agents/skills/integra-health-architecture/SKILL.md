---
name: integra-health-architecture
description:
  Arquitectura y convenciones del repo Integra Health (Laravel 13.39 + Filament 5.11,
  panel de invitado, app local de un solo usuario). Cargar en cualquier feature,
  bugfix, onboarding o cambio de infraestructura del repo.
---

# Integra Health — Arquitectura

## Propósito

Mapa estructural y reglas de infraestructura de Integra Health: cómo está montada la
app, dónde vive cada cosa y qué comandos se usan. No describe reglas de negocio de
métricas (ver skill `health-tracking-domain`) ni el catálogo de alimentos (ver skill
`food-portion-catalog`).

## Cuándo usarla

- Cualquier feature, bugfix o refactor en este repo.
- Onboarding: entender la forma de la app antes de tocar código.
- Cambios de infraestructura: migraciones, seeders, testing, tooling, CI, git.

## Modelo de app

- App **local de un solo usuario por instalación**: cada compañero clona el repo
  (`moonslayers/integra-health`) y usa su propia base SQLite. No hay multiusuario,
  no hay `user_id` en los modelos de dominio y **no hay login**.
- Stack: Laravel `13.39.0`, Filament `5.11.0`, PHP 8.5, SQLite, PHPUnit 12.
- Panel Filament en `/` con acceso de **invitado**; `/login` no existe (404).
  `AdminPanelProvider` (`app/Providers/Filament/AdminPanelProvider.php`) usa
  `->path('')`, `->default()`, brand `Integra Health`, color primary Emerald y
  discovery de Resources/Pages/Widgets en `app/Filament/*`.
- Timezone: `APP_TIMEZONE=America/Tijuana` en `.env` / `.env.example`, leído vía
  `config('app.timezone')` (ver `config/app.php`). **No** se guarda timezone en la
  base de datos ni en `health_settings`; todo cálculo de fechas pasa por
  `config('app.timezone')` (lo hace `HealthMetricsService`).

## Mapa de archivos

```
app/Models/                  Food, FoodEntry, WaterIntake, ActivePause,
                             ExerciseSession, HealthSetting (User es esqueleto, sin uso)
app/Enums/                   WaterType, PauseType, FoodCategory, ExerciseType, ServingUnit
app/Services/                HealthMetricsService.php (fuente canónica de cálculos)
app/Filament/Resources/      ActivePauses, ExerciseSessions, Food, FoodEntries, WaterIntakes
                             (cada una: FoodResource.php + Pages/ + Schemas/ + Tables/)
app/Filament/Pages/          Dashboard.php (acciones rápidas), HealthSettings.php
app/Filament/Widgets/        HealthStatsOverview, RecentActivity, Water*, FruitVeg*,
                             ActivePausesChart, ExerciseWeeklyChart
app/Filament/Concerns/       HasHealthRefresh, HasRangeFilter, HasInlineFoodCreation
app/Providers/Filament/      AdminPanelProvider.php
database/migrations/         2026_10_29_10000x_* (tablas de dominio) + esqueleto Laravel
database/seeders/            DatabaseSeeder.php, FoodSeeder.php, data/foods.php
database/factories/          FoodFactory, FoodEntryFactory, WaterIntakeFactory, etc.
tests/Feature/               tests HTTP del panel y de recursos (sin actingAs)
tests/Unit/Models/           tests de modelos; tests/Unit/Services/HealthMetricsServiceTest.php
```

## Traits de Filament (`app/Filament/Concerns/`)

- `HasHealthRefresh`: escucha `health-data-updated` y refresca el widget/página.
  Quien crea datos (`Dashboard::waterAction`, `pauseAction`, `foodAction`,
  `exerciseAction`) hace `$this->dispatch('health-data-updated')`. Todo widget que
  muestra datos de salud debe usar este trait.
- `HasRangeFilter`: filtro 7/30/90 días para gráficas (`$this->filter`,
  `rangeDays()`); default `30`.
- `HasInlineFoodCreation`: alta de alimento desde el Select (ver skill
  `food-portion-catalog`).

## Comandos

```bash
composer run setup     # install, .env, key, SQLite, migrate, seed, npm install, npm build
composer run health    # php artisan dev (server + queue + logs + vite)
php artisan test --compact
vendor/bin/pint --dirty --format agent   # OBLIGATORIO tras modificar PHP
```

## Git / repo

- Repo público: `https://github.com/moonslayers/integra-health`.
- **Nunca** commitear: `.env`, `database/*.sqlite`, `vendor/`, `node_modules/`,
  `public/build/` (todo está en `.gitignore`; revisar `git status` antes de `git add`).

## Testing

- PHPUnit (`phpunit.xml`): SQLite **en memoria** (`DB_DATABASE=:memory:`),
  `APP_ENV=testing`, `RefreshDatabase` en tests de datos.
- **No hay auth**: nunca usar `actingAs()`, `Auth::login()` ni rutas protegidas;
  el panel se prueba como invitado (`tests/Feature/GuestPanelTest.php` espera 200
  en `/` y 404 en `/login`).
- Para fechas: `Carbon::setTestNow(Carbon::parse(..., config('app.timezone')))` y
  restaurar en `tearDown` (patrón en `tests/Feature/DashboardWidgetsTest.php` y
  `tests/Unit/Services/HealthMetricsServiceTest.php`).
- Correr el subconjunto más angosto que cubra el cambio:
  `php artisan test --compact tests/Feature/FoodResourceTest.php`.

## Pitfalls

- No crear login, policies por usuario ni columnas `user_id` en tablas de dominio.
- No editar migraciones ya ejecutadas en otros clones: crear migración nueva.
- No registrar timezone en la BD; usar `config('app.timezone')`.
- Tablas `users`, `password_reset_tokens` y `sessions` son esqueleto sin uso.
- Si un cambio de UI no aparece: `npm run build` o `composer run health`.

## Relación con otras skills

- `health-tracking-domain`: reglas de negocio y `HealthMetricsService`.
- `food-portion-catalog`: catálogo de alimentos y alta inline.
- `filament-development`, `laravel-best-practices`, `testing-best-practices`,
  `tailwindcss-development`: guías genéricas; esta skill aporta el contexto local.
