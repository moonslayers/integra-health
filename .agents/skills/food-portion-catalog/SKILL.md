---
name: food-portion-catalog
description:
  Catálogo de alimentos de Integra Health: FoodSeeder, foods.php (55 alimentos),
  equivalencias de porciones, counts_toward_goal y alta inline con
  HasInlineFoodCreation. Cargar al agregar o editar alimentos, equivalencias,
  el catálogo o la creación inline de alimentos.
---

# Integra Health — Catálogo de porciones de alimentos

## Propósito

Fuente de verdad del catálogo de alimentos y de cómo se traducen a porciones de
fruta/verdura. Reglas de alta (seed e inline) y de clasificación.

## Cuándo usarla

- Agregar, editar o reclasificar alimentos y sus equivalencias (`portions_per_serving`).
- Cambiar el seeder, el catálogo o el formulario de alta inline.
- Bugs de porciones mal contadas o alimentos duplicados.

## Mapa de archivos

```
database/seeders/data/foods.php        array de 55 alimentos (fuente de datos)
database/seeders/FoodSeeder.php        updateOrCreate por slug (idempotente)
database/migrations/2026_10_29_100003_create_foods_table.php
app/Models/Food.php                    name, slug (unique), category, serving_unit,
                                       portions_per_serving, counts_toward_goal, source, notes
app/Enums/FoodCategory.php             fruit, vegetable, legume, protein, grain, dairy, other
app/Enums/ServingUnit.php              piece, cup, portion, gram
app/Filament/Concerns/HasInlineFoodCreation.php
app/Filament/Resources/Food/Schemas/FoodForm.php          alta/edit por pantalla
app/Filament/Resources/FoodEntries/Schemas/FoodEntryForm.php  usa el Select con inline
tests/Feature/FoodSeederTest.php       idempotencia + ejemplos autoritativos
```

## Seeder e idempotencia

- `FoodSeeder` hace `Food::updateOrCreate(['slug' => $food['slug']], [...])` sobre
  `require database/seeders/data/foods.php`. Correrlo repetidamente **no duplica**
  (cubierto por `FoodSeederTest::test_seeding_twice_does_not_duplicate_foods`).
- **Para agregar un alimento**: añadir una entrada en `data/foods.php` con `slug`
  único; nunca crear un archivo de seeder nuevo ni insertar a mano en la BD de otro
  compañero. `composer run setup` (o `php artisan db:seed`) lo propagará.
- Campos por entrada: `name`, `slug`, `category`, `serving_unit`,
  `portions_per_serving`, `counts_toward_goal`, `source`, `notes`.

## Reglas de clasificación y porciones

- Solo **frutas** (`fruit`) y **verduras** (`vegetable`) llevan
  `counts_toward_goal = true`. Legumbres, proteínas, granos, lácteos y otros:
  `counts_toward_goal = false` y `portions_per_serving = null` (no suman a la meta).
- Fuentes: el campo `source` registra la referencia; el catálogo base es SMAE
  (Secretaría de Medicina Alimentaria y Ejercicio), admite referencias NOM.
- Ejemplos autoritativos (asserts en `FoodSeederTest`):
  - `manzana-roja`: fruit, piece, **2** porciones, cuenta para la meta.
  - `fresas`: fruit, cup (1 taza), **1** porción, cuenta.
  - `brocoli`: vegetable, portion, **1** porción, cuenta.
  - `zanahoria`: vegetable, portion, **1** porción, cuenta.
- Otros casos: lechuga/espinaca/cebolla 0.5 porción por taza; plátano y mango 2.
- La papa es `vegetable` con `counts_toward_goal = true` (clasificación
  SMAE del catálogo); no moverla a `other` sin sustento.
- Porciones de un registro: `quantity × portions_per_serving`
  (ver skill `health-tracking-domain`).

## Alta inline (`HasInlineFoodCreation`)

- Trait en `app/Filament/Concerns/HasInlineFoodCreation.php`; lo usan
  `FoodEntryForm` y `Dashboard::foodAction` para envolver el Select de alimento con
  `createOptionUsing` / `createOptionForm`.
- Formulario compacto: `name`, `category`, `serving_unit`, toggle
  `counts_toward_goal` (default false), `portions_per_serving` (solo visible y
  **requerido** si el toggle está activo), `source`, `notes`.
- Slug único automático: `generateUniqueFoodSlug($name)` (`Str::slug` + sufijo
  `-2`, `-3`… si ya existe). El alta en pantalla (`FoodForm`) usa slug manual con
  `->unique()` y autogenerado desde el nombre.
- Al crear desde el Select, el alimento **queda seleccionado**: el modal devuelve
  el `id` nuevo y Filament lo deja como valor del `food_id`.
- `portions_per_serving` se guarda `null` si `counts_toward_goal` es false.

## Pitfalls

- No editar migraciones viejas para cambiar datos del catálogo: van en `foods.php`.
- No contar legumbres (ni proteínas/granos/lácteos) hacia la meta de frutas/verduras.
- No usar slugs duplicados: el `slug` es la clave de idempotencia del seeder.
- Validar `portions_per_serving` (obligatorio y > 0) cuando
  `counts_toward_goal = true`, en seeder y en formularios.
- No recalcular porciones en recursos/widgets aparte del accessor/servicio.

## Relación con otras skills

- `health-tracking-domain`: cómo estas porciones alimentan `todaySummary` y metas.
- `integra-health-architecture`: ubicación de archivos, seeder y comandos.
- `filament-development`: patrones de formularios/Select de Filament 5.
