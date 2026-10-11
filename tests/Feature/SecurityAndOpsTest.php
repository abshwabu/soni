<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityAndOpsTest extends TestCase
{
    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    public function test_page_rich_text_content_is_sanitized_against_xss(): void
    {
        $rawMaliciousHtml = <<<HTML
<h2>Luxury Fragrances</h2>
<script>alert('malicious')</script>
<p>Authentic perfumes in Addis Ababa.</p>
<iframe src="https://attacker.com/cookie-steal"></iframe>
<img src="valid.jpg" onerror="alert('xss')" alt="Bottle" />
<a href="javascript:stealToken()">Click for free perfume</a>
HTML;

        $page = Page::create([
            'title' => 'Terms of Authenticity',
            'slug' => 'terms-authenticity',
            'content' => $rawMaliciousHtml,
            'is_published' => true,
        ]);

        $this->assertStringNotContainsString('<script', $page->content);
        $this->assertStringNotContainsString('</script>', $page->content);
        $this->assertStringNotContainsString('<iframe', $page->content);
        $this->assertStringNotContainsString('onerror', $page->content);
        $this->assertStringNotContainsString('javascript:', $page->content);

        // Safe HTML is preserved
        $this->assertStringContainsString('<h2>Luxury Fragrances</h2>', $page->content);
        $this->assertStringContainsString('<p>Authentic perfumes in Addis Ababa.</p>', $page->content);

        $page->delete();
    }

    public function test_telegram_service_redacts_bot_token_in_logs(): void
    {
        $service = new TelegramService('123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ', '987654321');

        $logInput = 'Request to https://api.telegram.org/bot123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ/sendMessage failed';
        $sanitized = $service->sanitizeLog($logInput);

        $this->assertStringNotContainsString('123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ', $sanitized);
        $this->assertStringContainsString('[REDACTED_BOT_TOKEN]', $sanitized);
    }

    public function test_subscribers_endpoint_rate_limits_excessive_requests(): void
    {
        RateLimiter::clear('subscribers:127.0.0.1');

        // Send 10 successful requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson(route('subscribers.store'), [
                'email_or_phone' => "user{$i}@example.com",
            ]);
            $response->assertStatus(200);
        }

        // 11th request exceeds limit
        $response = $this->postJson(route('subscribers.store'), [
            'email_or_phone' => 'overlimit@example.com',
        ]);
        $response->assertStatus(429);
    }

    public function test_sqlite_maintenance_command_executes_successfully(): void
    {
        $exitCode = Artisan::call('db:maintenance-sqlite');
        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('SQLite maintenance completed successfully', $output);
    }

    public function test_sqlite_backup_command_creates_compressed_snapshots(): void
    {
        $testBackupDir = storage_path('app/test_backups_' . uniqid());

        $exitCode = Artisan::call('db:backup', [
            '--destination' => $testBackupDir,
            '--no-media' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(File::isDirectory($testBackupDir));

        $files = File::files($testBackupDir);
        $this->assertNotEmpty($files);

        // Check that .gz archives exist
        $hasGz = false;
        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.sqlite.gz')) {
                $hasGz = true;
                break;
            }
        }
        $this->assertTrue($hasGz, 'Expected at least one .sqlite.gz backup file');

        // Cleanup test directory
        File::deleteDirectory($testBackupDir);
    }

    public function test_sqlite_restore_command_validates_candidate_integrity(): void
    {
        // 1. Invalid or corrupted file fails integrity check
        $fakeFile = storage_path('app/corrupted_' . uniqid() . '.sqlite');
        File::put($fakeFile, 'THIS_IS_NOT_A_VALID_SQLITE_DATABASE');

        $exitCode = Artisan::call('db:restore', [
            'file' => $fakeFile,
            '--force' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Integrity check failed', $output);

        @unlink($fakeFile);
    }

    public function test_unauthenticated_user_cannot_access_filament_admin(): void
    {
        $response = $this->get('/admin');
        // Unauthenticated users are redirected to admin login
        $response->assertRedirect('/admin/login');
    }

    public function test_queue_job_failure_dispatches_telegram_alert(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 777]], 200),
        ]);

        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.admin_chat_id' => '123456789',
        ]);

        $fakeJob = new SyncJob(app(), '{"job":"Tests\\\\Feature\\\\DummyFailingJob","data":[]}', 'sync', 'default');
        $exception = new \RuntimeException('Database disk image is malformed or worker timeout');

        event(new JobFailed('sync', $fakeJob, $exception));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'Queue Job Failed Alert')
                && str_contains($request['text'], 'Database disk image is malformed');
        });
    }
}
