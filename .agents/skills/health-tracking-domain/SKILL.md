---
name: health-tracking-domain
description:
  Reglas de negocio de métricas de salud de Integra Health: agua, pausas, porciones
  de fruta/verdura, ejercicio y metas en HealthSetting. Cargar en cambios de
  métricas, metas, dashboard, gráficas, reportes o cálculos de HealthMetricsService.
---

# Integra Health — Dominio de métricas

## Propósito

Reglas de negocio de las cuatro métricas (agua, pausas, alimentos, ejercicio), metas
y el servicio canónico que las calcula. Si el cambio toca números que ve el usuario,
esta skill manda.

## Cuándo usarla

- Cambios en cálculos, metas, dashboard, gráficas o reportes.
- Agregar métricas nuevas o modificar cómo se comparan contra metas.
- Bugs de conteos, porcentajes, series diarias o resumen semanal.

## Mapa de archivos

```
app/Services/HealthMetricsService.php      fuente canónica de cálculos
app/Models/HealthSetting.php               metas (HealthSetting::current())
app/Models/WaterIntake.php                 type, volume_ml, consumed_at, notes
app/Models/ActivePause.php                 minutes, type, performed_at
app/Models/FoodEntry.php                   quantity, consumed_at, notes + accessor portions
app/Models/Food.php                        portions_per_serving, counts_toward_goal
app/Models/ExerciseSession.php             minutes, type, performed_at, notes
app/Enums/WaterType.php | PauseType.php | ExerciseType.php | FoodCategory.php
app/Filament/Pages/Dashboard.php           acciones de registro + widgets
app/Filament/Widgets/*.php                 leen el servicio; no recalculan
tests/Unit/Services/HealthMetricsServiceTest.php
tests/Feature/DashboardWidgetsTest.php
```

## Servicio canónico: `HealthMetricsService`

Inyectar con `app(HealthMetricsService::class)`. Métodos públicos:

- `portionsForFoodEntry(FoodEntry $entry): float` — porciones de un registro.
- `todaySummary(?Carbon|string $date): array` — `date`, `water_total_ml`,
  `water_count`, `water_by_type`, `fruit_veg_portions`, `pauses_count`,
  `pauses_minutes`.
- `goalProgress(?Carbon|string $date): array` — por meta con `current`, `goal`,
  `percentage`, `met` más `exercise`.
- `dashboardSummary(?Carbon|string $date): array` — `goalProgress` + clave `today`.
- `dailySeries(string $from, string $to): array` — por día: `water_volume_ml`,
  `water_count`, `fruit_veg_portions`, `pauses_count`, `pauses_minutes`.
- `weeklyExerciseSummary(?Carbon|string $date): array` — minutos y días de la semana
  con sus metas/porcentajes.
- Auxiliares: `timezone()`, `now()`, `todayDate()`.

**Nunca** duplicar estas queries en widgets, resources o páginas: todo consumo pasa
por el servicio.

## Reglas de negocio

### Agua
- Tipos (`WaterType`): natural, infusión fría (`cold_infusion`), infusión caliente
  (`hot_infusion`), café, mineral. **Todas cuentan igual** para la meta: solo se
  suma `volume_ml`, sin ponderar por tipo.
- `volume_ml` opcional: si va vacío se usa `HealthSetting::current()->default_water_serving_ml`
  (default 250 ml; también default de columna en la migración).
- `notes` opcional.
- **Frecuencia = número de registros** (`water_count` = COUNT), no suma de volúmenes.

### Pausas activas
- `minutes` default **5** (`HealthSetting::active_pause_default_minutes`).
- Tipo: estiramiento (`stretching`) o pasiva (`passive`).
- Meta: `active_pause_goal_count` por día.

### Alimentos (fruta/verdura)
- Porciones de un registro: `quantity × foods.portions_per_serving`
  (`FoodEntry::getPortionsAttribute` / `portionsForFoodEntry`); si
  `portions_per_serving` es null → 0.
- Solo `counts_toward_goal = true` suma a la meta. Legumbres, carnes, granos,
  lácteos y otros **no suman** aunque tengan `portions_per_serving`.
- Meta default: `fruit_veg_goal_portions = 1` porción/día.
- El resumen filtra con join a `foods` y `foods.counts_toward_goal = true`.
- Cada registro admite `notes` opcional (una nota por alimento registrado): se
  captura en el resource FoodEntries y en la acción `registrarAlimento` del
  Dashboard, se guarda en `food_entries.notes` y se muestra en la tabla de
  FoodEntries.
- `quantity` se expresa en la `serving_unit` del alimento. El texto de unidad
  que ve el usuario se centraliza en `ServingUnit::getPluralLabel()`,
  `Food::unitLabelForQuantity()`, `Food::quantityWithUnit()` y
  `Food::nameWithUnit()`: es solo presentación, la matemática de porciones
  sigue siendo `quantity × portions_per_serving`.

### Ejercicio
- Dos metas **separadas**: minutos semanales (`exercise_weekly_minutes_goal`,
  default 60) y días activos semanales (`exercise_weekly_days_goal`, default 3).
- La **semana empieza lunes** (`startOfWeek(Carbon::MONDAY)` en `weekBounds`);
  días activos = fechas únicas con al menos una sesión.

### Metas
- Todas viven en `health_settings`, singleton vía `HealthSetting::current()`
  (`firstOrCreate([])`). Campos: `water_goal_ml` (2000), `fruit_veg_goal_portions` (1),
  `active_pause_goal_count` (2), `active_pause_default_minutes` (5),
  `exercise_weekly_minutes_goal` (60), `exercise_weekly_days_goal` (3),
  `default_water_serving_ml` (250). Se editan en `app/Filament/Pages/HealthSettings.php`.

## Fechas y timezone

- Todo cálculo usa `config('app.timezone')` = `America/Tijuana` vía `dayBounds` /
  `weekBounds` del servicio. Nunca asumir UTC ni `now()` sin timezone en el dominio.
- Cuidado con bordes de día: un registro a las 00:30 de Tijuana pertenece a ese día,
  no al anterior (caso de test en `HealthMetricsServiceTest::test_day_boundary_timezone`).

## Pitfalls

- No usar `auth()` / `user_id`: no hay login.
- No poner timezone en `health_settings` ni en la BD.
- No contar legumbres (ni proteínas/granos/lácteos) hacia la meta de porciones.
- No duplicar queries de resumen en widgets; usar `dashboardSummary`/`dailySeries`.
- No fusionar las metas de ejercicio (minutos ≠ días).
- No editar migraciones ya aplicadas para "arreglar" defaults.
- No reimplementar etiquetas/plurales de unidades ni el manejo de `notes` por
  registro en widgets, resources o acciones del Dashboard: usar los helpers del
  modelo y el campo de notas existente.

## Relación con otras skills

- `integra-health-architecture`: estructura, comandos, testing, panel de invitado.
- `food-portion-catalog`: equivalencias de `portions_per_serving` por alimento.
- `testing-best-practices` + `laravel-best-practices`: guías genéricas de tests/código.
