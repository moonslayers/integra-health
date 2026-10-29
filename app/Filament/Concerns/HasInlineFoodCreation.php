<?php

namespace App\Filament\Concerns;

use App\Enums\FoodCategory;
use App\Enums\ServingUnit;
use App\Models\Food;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;

trait HasInlineFoodCreation
{
    public static function applyInlineFoodCreation(Select $select): Select
    {
        return $select
            ->createOptionModalHeading('Crear alimento')
            ->createOptionForm(static::inlineFoodCreationForm())
            ->createOptionUsing(static function (array $data): int {
                return static::createFoodFromOptionData($data);
            });
    }

    /**
     * @return array<int, Component>
     */
    public static function inlineFoodCreationForm(): array
    {
        return [
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            Select::make('category')
                ->label('Categoría')
                ->options(FoodCategory::class)
                ->required(),
            Select::make('serving_unit')
                ->label('Unidad de servicio')
                ->options(ServingUnit::class)
                ->required(),
            Toggle::make('counts_toward_goal')
                ->label('Cuenta para la meta')
                ->default(false)
                ->live(),
            TextInput::make('portions_per_serving')
                ->label('Porciones por unidad')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->visible(fn (Get $get): bool => (bool) $get('counts_toward_goal'))
                ->required(fn (Get $get): bool => (bool) $get('counts_toward_goal')),
            TextInput::make('source')
                ->label('Fuente')
                ->maxLength(255),
            Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
        ];
    }

    public static function createFoodFromOptionData(array $data): int
    {
        $countsTowardGoal = (bool) ($data['counts_toward_goal'] ?? false);

        $food = Food::create([
            'name' => $data['name'],
            'slug' => static::generateUniqueFoodSlug($data['name']),
            'category' => $data['category'],
            'serving_unit' => $data['serving_unit'],
            'counts_toward_goal' => $countsTowardGoal,
            'portions_per_serving' => $countsTowardGoal ? ($data['portions_per_serving'] ?? null) : null,
            'source' => $data['source'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $food->getKey();
    }

    public static function generateUniqueFoodSlug(string $name): string
    {
        $slug = Str::slug($name) ?: 'alimento';
        $base = $slug;
        $suffix = 2;

        while (Food::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
