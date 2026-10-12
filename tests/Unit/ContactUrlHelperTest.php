<?php

namespace Tests\Unit;

use App\Support\ContactUrlHelper;
use PHPUnit\Framework\TestCase;

class ContactUrlHelperTest extends TestCase
{
    public function test_formats_whatsapp_from_local_ethiopian_phone(): void
    {
        $this->assertEquals(
            'https://wa.me/251911234567',
            ContactUrlHelper::formatWhatsAppUrl('0911234567')
        );

        $this->assertEquals(
            'https://wa.me/251712345678',
            ContactUrlHelper::formatWhatsAppUrl('0712345678')
        );
    }

    public function test_formats_whatsapp_from_formatted_international_phone(): void
    {
        $this->assertEquals(
            'https://wa.me/251911234567',
            ContactUrlHelper::formatWhatsAppUrl('+251 91 123 4567')
        );

        $this->assertEquals(
            'https://wa.me/251911234567',
            ContactUrlHelper::formatWhatsAppUrl('+251911234567')
        );
    }

    public function test_formats_whatsapp_from_full_url(): void
    {
        $this->assertEquals(
            'https://wa.me/251911234567',
            ContactUrlHelper::formatWhatsAppUrl('https://wa.me/251911234567')
        );

        $this->assertEquals(
            'https://wa.me/251911234567',
            ContactUrlHelper::formatWhatsAppUrl('wa.me/251911234567')
        );
    }

    public function test_formats_whatsapp_with_prefilled_message(): void
    {
        $this->assertEquals(
            'https://wa.me/251911234567?text=Hello+Soni',
            ContactUrlHelper::formatWhatsAppUrl('0911234567', 'Hello Soni')
        );
    }

    public function test_formats_telegram_from_username(): void
    {
        $this->assertEquals(
            'https://t.me/soniperfume_admin',
            ContactUrlHelper::formatTelegramUrl('@soniperfume_admin')
        );

        $this->assertEquals(
            'https://t.me/abshwabu',
            ContactUrlHelper::formatTelegramUrl('abshwabu')
        );

        $this->assertEquals(
            'https://t.me/my_bot',
            ContactUrlHelper::formatTelegramUrl('t.me/my_bot')
        );
    }

    public function test_formats_telegram_from_phone_number(): void
    {
        $this->assertEquals(
            'https://t.me/+251911234567',
            ContactUrlHelper::formatTelegramUrl('+251911234567')
        );

        $this->assertEquals(
            'https://t.me/+251911234567',
            ContactUrlHelper::formatTelegramUrl('0911234567')
        );

        $this->assertEquals(
            'https://t.me/+251911234567',
            ContactUrlHelper::formatTelegramUrl('+251 91 123 4567')
        );
    }

    public function test_formats_telegram_from_full_url(): void
    {
        $this->assertEquals(
            'https://t.me/soniperfume_admin',
            ContactUrlHelper::formatTelegramUrl('https://t.me/soniperfume_admin')
        );
    }

    public function test_formats_telegram_with_prefilled_message(): void
    {
        $this->assertEquals(
            'https://t.me/soniperfume_admin?text=Hello',
            ContactUrlHelper::formatTelegramUrl('soniperfume_admin', 'Hello')
        );
    }
}
