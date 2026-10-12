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

                Forms\Components\Section::make('Customer Contact Channels')
                    ->description('Direct phone, WhatsApp, and Telegram connections for customer inquiries and orders.')
                    ->schema([
                        Forms\Components\TextInput::make('store_phone')
                            ->label('Store Phone Number')
                            ->placeholder('+251 91 123 4567')
                            ->helperText('Displayed in the top bar, footer, and contact sections.')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('store_whatsapp')
                            ->label('WhatsApp (Phone Number or Link)')
                            ->placeholder('+251 91 123 4567, 0911234567, or https://wa.me/251911234567')
                            ->helperText('Redirects visitors from the front page directly to chat with this user/number.')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('telegram_url')
                            ->label('Telegram (Username, Phone, or Link)')
                            ->placeholder('@username, +251 91 123 4567, or https://t.me/username')
                            ->helperText('Redirects visitors from the front page to this Telegram user, bot, or phone.')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Social Media Profiles')
                    ->description('Links to your official social profiles.')
                    ->schema([
                        Forms\Components\TextInput::make('instagram_url')
                            ->label('Instagram URL')
                            ->placeholder('https://instagram.com/soniperfume')
                            ->url(),

                        Forms\Components\TextInput::make('tiktok_url')
                            ->label('TikTok URL')
                            ->placeholder('https://tiktok.com/@soniperfume')
                            ->url(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            Setting::set($key, (string) ($value ?? ''));
        }

        // Invalidate settings caches so front page reflects updates immediately
        \Illuminate\Support\Facades\Cache::forget('store_settings');
        \Illuminate\Support\Facades\Cache::forget('home_page_data');

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
