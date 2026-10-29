---
name: filament-development
description:
  Builds Filament application and admin panel interfaces. Use when creating,
  modifying, testing, or debugging Filament panels, resources, relation managers,
  pages, schemas, forms, tables, actions, infolists, widgets, imports, or exports.
---

# Filament Development

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

## Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan list`.
- Inspect required options before running, and always pass `--no-interaction`.

## Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

### Conditional form field visibility

```php
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),
```

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

### Reactive field update

```php
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),
```

Compose layout by nesting `Section` and `Grid`. Children span one column by default. Use `->columnSpan()` to span multiple columns or `->columnSpanFull()` to span the full width:

### `Section` and `Grid` layout

```php
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name'),
            TextInput::make('last_name'),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),
```

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

### `Repeater` for `HasMany`

```php
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),
```

Use `state()` with a `Closure` to compute derived column values:

### Computed table column value

```php
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),
```

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

### Table filters

```php
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),
```

Actions are buttons that encapsulate optional modal forms and behavior:

### Action with modal form

```php
use Filament\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),
```

## Testing

Testing setup — this repo uses **PHPUnit**, not Pest (`php artisan make:test --phpunit`). The Pest-style examples below are generic/illustrative; in this repo translate them to PHPUnit with Livewire testing helpers. If a project uses Pest, then it requires `pestphp/pest-plugin-livewire` in `composer.json`:

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- **Exception — guest panels:** if the panel has no authentication (no `Authenticate` middleware, mounted at `/`), never call `actingAs()`. Test HTTP responses and Livewire components as a guest.
- For edit pages, pass `['record' => $user->id]` and use `->call('save')` (not `->call('create')`). Edit pages do not redirect after saving by default, so only assert a redirect when one is configured or `getRedirectUrl()` is overridden.

### Table test

```php
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));
```

### Create resource test

```php
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);
```

### Edit resource test

```php
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);
```

### Testing validation

```php
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();
```

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

### Guest panel test

```php
// No actingAs() — the panel is public.
$response = $this->get('/');

$response->assertStatus(200);

livewire(Dashboard::class)
    ->assertSuccessful();
```

### Calling actions

```php
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();
```

## Correct namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

## Common mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for `BelongsTo` fields.** `BelongsToSelect` does not exist; use `Select::relationship()`.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

## Guest panels (root path)

Mount a panel at `/` with `->path('')`. A guest (unauthenticated) panel must not include auth plumbing:

- Omit `->login()`, `->authGuard()`, `->profile()`, and the `Authenticate` / `AuthenticateSession` middleware. If there is no auth at all, `AuthenticateSession` must not remain in the middleware stack.
- Do not register `AccountWidget` (or any auth-dependent widget).

`->pages([])` combined with `->discoverPages()` registers pages only via discovery, so Filament does not also inject its stock Dashboard next to a custom one:

```php
return $panel
    ->default()
    ->id('admin')
    ->path('')
    ->brandName('Integra Health')
    ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
    ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
    ->pages([])
    ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
    ->middleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
        SubstituteBindings::class,
        DisableBladeIconComponents::class,
        DispatchServingFilamentEvent::class,
    ]);
```

Verify the setup: `GET /` must return `200`, and `GET /login` must return `404` (no login route registered).

## Chart widgets with filters

For structured filters on a `ChartWidget`, use `Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema`. Define the filter form in `filtersSchema()` and read values from `$this->filters` (a live array keyed by field name). Changing a live filter clears the widget's cached chart data automatically:

```php
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class IntakeChart extends ChartWidget
{
    use HasFiltersSchema;

    protected function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('range')
                ->options([
                    '7' => '7 días',
                    '30' => '30 días',
                    '90' => '90 días',
                ])
                ->default('30')
                ->live(),
        ]);
    }

    protected function getData(): array
    {
        $days = (int) ($this->filters['range'] ?? 30);

        // build chart payload from $days
    }
}
```

Always provide a `->default()` for the range so `$this->filters` is never empty on first render.

## Inline create option on `Select`

Let users create a related record from the Select modal with `createOptionForm()` + `createOptionUsing()`. `createOptionUsing()` must return the new record's key so Filament selects the created option immediately:

```php
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

Select::make('food_id')
    ->relationship('food', 'name')
    ->createOptionModalHeading('Crear alimento')
    ->createOptionForm([
        TextInput::make('name')->required(),
    ])
    ->createOptionUsing(function (array $data): int {
        $slug = Str::slug($data['name']) ?: 'registro';
        $base = $slug;
        $suffix = 2;

        while (Food::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return Food::create([
            'name' => $data['name'],
            'slug' => $slug,
        ])->getKey();
    });
```

Generate unique slugs (or other natural keys) inside `createOptionUsing()`, not in the form.

## Widget refresh on data changes

Dispatch a Livewire event after a mutation, and listen for it on every widget that renders affected data. An empty listener body is intentional: Livewire re-renders the component when the event arrives. Put the attribute and empty method in a shared trait so widgets do not duplicate the listener:

```php
// app/Filament/Concerns/HasHealthRefresh.php
use Livewire\Attributes\On;

trait HasHealthRefresh
{
    #[On('health-data-updated')]
    public function refreshData(): void {}
}
```

```php
class WaterVolumeChart extends ChartWidget
{
    use HasHealthRefresh;
    // ...
}
```

```php
// In the page/action that mutates data:
$this->dispatch('health-data-updated');
```
