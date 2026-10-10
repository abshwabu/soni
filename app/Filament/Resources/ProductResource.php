<?php

namespace App\Filament\Resources;

use App\Enums\Concentration;
use App\Enums\Gender;
use App\Enums\ScentFamily;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Product Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (string $operation, ?string $state, Forms\Set $set): void {
                                                if ($operation === 'create' && filled($state)) {
                                                    $set('slug', Str::slug($state));
                                                }
                                            })
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(Product::class, 'slug', ignoreRecord: true)
                                            ->columnSpan(1),

                                        Forms\Components\Select::make('brand_id')
                                            ->relationship('brand', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->label('Brand'),

                                        Forms\Components\Select::make('category_id')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->label('Category'),

                                        Forms\Components\Select::make('gender')
                                            ->options(collect(Gender::cases())->mapWithKeys(fn ($g) => [$g->value => $g->label()]))
                                            ->required()
                                            ->label('Gender Target'),

                                        Forms\Components\Select::make('concentration')
                                            ->options(collect(Concentration::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                                            ->required()
                                            ->label('Concentration'),

                                        Forms\Components\Select::make('scent_family')
                                            ->options(collect(ScentFamily::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                                            ->required()
                                            ->label('Scent Family'),
                                    ]),

                                Forms\Components\Textarea::make('short_description')
                                    ->maxLength(500)
                                    ->rows(2)
                                    ->helperText('A quick summary visible on product cards and listings.')
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('description')
                                    ->columnSpanFull()
                                    ->helperText('Detailed product backstory, inspiration, and olfactory journey.'),

                                Forms\Components\Section::make('Badges & Visibility')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active on Storefront')
                                            ->default(true),

                                        Forms\Components\Toggle::make('is_featured')
                                            ->label('Featured Product')
                                            ->default(false),

                                        Forms\Components\Toggle::make('is_new')
                                            ->label('New Arrival')
                                            ->default(false),

                                        Forms\Components\Toggle::make('is_bestseller')
                                            ->label('Bestseller')
                                            ->default(false),
                                    ])
                                    ->columns(4),
                            ]),

                        Forms\Components\Tabs\Tab::make('Scent Notes')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Forms\Components\Section::make('Fragrance Pyramid')
                                    ->description('Olfactory notes structure of the perfume')
                                    ->schema([
                                        Forms\Components\Textarea::make('top_notes')
                                            ->label('Top Notes')
                                            ->placeholder('e.g. Italian Bergamot, Pink Pepper, Sicilian Lemon')
                                            ->helperText('The initial impression perceived right after spraying (lasts 5-15 mins).')
                                            ->rows(3),

                                        Forms\Components\Textarea::make('heart_notes')
                                            ->label('Heart (Middle) Notes')
                                            ->placeholder('e.g. Bulgarian Rose, French Jasmine, Indonesian Patchouli')
                                            ->helperText('The core essence of the perfume as top notes fade (lasts 2-4 hours).')
                                            ->rows(3),

                                        Forms\Components\Textarea::make('base_notes')
                                            ->label('Base Notes')
                                            ->placeholder('e.g. Royal Oud, Ambergris, Bourbon Vanilla, White Musk')
                                            ->helperText('The enduring foundation that lingers on skin and clothes all day.')
                                            ->rows(3),
                                    ])
                                    ->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make('Variants')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Forms\Components\Repeater::make('variants')
                                    ->relationship('variants')
                                    ->schema([
                                        Forms\Components\TextInput::make('size_ml')
                                            ->label('Size (ml)')
                                            ->numeric()
                                            ->required()
                                            ->suffix('ml'),

                                        Forms\Components\TextInput::make('price')
                                            ->label('Price (ETB)')
                                            ->numeric()
                                            ->required()
                                            ->prefix('ETB')
                                            ->minValue(0),

                                        Forms\Components\TextInput::make('compare_at_price')
                                            ->label('Original / Compare Price (ETB)')
                                            ->numeric()
                                            ->nullable()
                                            ->prefix('ETB')
                                            ->helperText('Optional crossed-out price to show discount'),

                                        Forms\Components\TextInput::make('sku')
                                            ->label('SKU')
                                            ->placeholder('e.g. SP-DIOR-SAV-100')
                                            ->nullable(),

                                        Forms\Components\TextInput::make('stock')
                                            ->label('Stock Qty')
                                            ->numeric()
                                            ->required()
                                            ->default(10)
                                            ->minValue(0),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true),
                                    ])
                                    ->columns(6)
                                    ->collapsible()
                                    ->cloneable()
                                    ->defaultItems(1)
                                    ->addActionLabel('Add Variant')
                                    ->itemLabel(fn (array $state): ?string => isset($state['size_ml'], $state['price'])
                                        ? "{$state['size_ml']}ml — ETB " . number_format((int) $state['price']) . " (Stock: " . ($state['stock'] ?? 0) . ")"
                                        : null
                                    )
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Images')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('images')
                                    ->collection('images')
                                    ->multiple()
                                    ->reorderable()
                                    ->image()
                                    ->imageEditor()
                                    ->helperText('Upload one or more bottle photos. Drag and drop to reorder; the first image will be used as the primary thumbnail.')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\TextInput::make('meta_title')
                                    ->maxLength(255)
                                    ->placeholder('Soni Perfume | Luxury Fragrances')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('meta_description')
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->placeholder('Order authentic designer perfumes with same-day delivery in Addis Ababa.')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('images')
                    ->conversion('thumb')
                    ->square()
                    ->defaultImageUrl('/images/placeholder.svg')
                    ->label('Thumbnail'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_from')
                    ->label('Price From')
                    ->state(fn (Product $record): string => $record->formatted_price_from),

                Tables\Columns\TextColumn::make('stock_total')
                    ->label('Total Stock')
                    ->state(fn (Product $record): int => (int) $record->variants->sum('stock'))
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_bestseller')
                    ->boolean()
                    ->label('Bestseller')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('Brand')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('gender')
                    ->options(collect(Gender::cases())->mapWithKeys(fn ($g) => [$g->value => $g->label()]))
                    ->label('Gender'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock (≤ 5)')
                    ->query(fn (Builder $query): Builder => $query->whereHas('variants', fn ($q) => $q->where('stock', '<=', 5)->where('is_active', true))),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['brand', 'category', 'variants', 'media']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
