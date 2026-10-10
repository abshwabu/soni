<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Store Settings';

    protected static ?string $title = 'Store Settings';

    protected static string $view = 'filament.pages.manage-settings';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Store Identity')
                    ->description('General store name, branding tagline, and about story.')
                    ->schema([
                        Forms\Components\TextInput::make('store_name')
                            ->label('Store Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tagline')
                            ->label('Store Tagline')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('about_text')
                            ->label('About Us Text')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Displayed on the about section of the storefront.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Delivery & Thresholds')
                    ->description('Delivery pricing and complimentary delivery criteria in Addis Ababa.')
                    ->schema([
                        Forms\Components\TextInput::make('delivery_fee_addis')
                            ->label('Standard Addis Delivery Fee')
                            ->numeric()
                            ->prefix('ETB')
                            ->required()
                            ->helperText('Fixed delivery fee in whole ETB.'),

                        Forms\Components\TextInput::make('free_delivery_threshold')
                            ->label('Free Delivery Threshold')
                            ->numeric()
                            ->prefix('ETB')
                            ->required()
                            ->helperText('Orders exceeding this amount receive free delivery.'),

                        Forms\Components\TextInput::make('welcome_discount_text')
                            ->label('Top Banner Announcement / Offer Text')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Promotional announcement displayed at the very top of the website.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Business Hours & Location')
                    ->description('Customer operating hours and physical store location.')
                    ->schema([
                        Forms\Components\TextInput::make('opening_hours')
                            ->label('Opening Hours')
                            ->placeholder('e.g. Mon–Sat: 9am–8pm | Sun: 10am–6pm')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('google_maps_embed')
                            ->label('Google Maps Embed URL / Code')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Google Maps iframe src URL for the store location in Addis Ababa.'),
                    ]),

                Forms\Components\Section::make('Social Media & Channels')
                    ->description('Direct links to your official social profiles and customer service bots.')
                    ->schema([
                        Forms\Components\TextInput::make('instagram_url')
                            ->label('Instagram URL')
                            ->placeholder('https://instagram.com/soniperfume')
                            ->url(),

                        Forms\Components\TextInput::make('tiktok_url')
                            ->label('TikTok URL')
                            ->placeholder('https://tiktok.com/@soniperfume')
                            ->url(),

                        Forms\Components\TextInput::make('telegram_url')
                            ->label('Telegram Channel / Bot')
                            ->placeholder('https://t.me/soniperfume_admin')
                            ->url(),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            Setting::set($key, (string) ($value ?? ''));
        }

        Notification::make()
            ->title('Store settings updated successfully!')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save')
                ->color('primary'),
        ];
    }
}
