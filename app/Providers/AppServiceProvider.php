<?php

namespace App\Providers;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Logout redirect ────────────────────────────────────────────────
        // Sends candidates to the landing page after logout. All other panels
        // fall back to their own login page via the Referer header.
        $this->app->bind(LogoutResponseContract::class, function () {
            return new class implements LogoutResponseContract {
                public function toResponse($request): Response
                {
                    if ($request->is('student*')) {
                        return redirect('/');
                    }
                    return redirect($request->header('Referer', '/'));
                }
            };
        });
    }

    public function boot(): void
    {
        // ── cPanel SMTP fix ────────────────────────────────────────────────
        $this->forceCpanelMailConfig();

        // ── S3 custom disk ─────────────────────────────────────────────────
        Storage::extend('s3-custom', function ($app, $config) {
            $client = new \Aws\S3\S3Client([
                'credentials' => [
                    'key'    => $config['key'],
                    'secret' => $config['secret'],
                ],
                'region'                  => $config['region'],
                'version'                 => 'latest',
                'endpoint'                => $config['endpoint'],
                'use_path_style_endpoint' => $config['use_path_style_endpoint'],
            ]);

            $adapter = new AwsS3V3Adapter(
                $client,
                $config['bucket'],
                $config['prefix'] ?? '',
                null,
                null,
                $config['options'] ?? []
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Fix Symfony Mailer DSN construction for cPanel shared hosting.
     *
     * ROOT CAUSE:
     * Laravel's MailManager::createSmtpTransport() builds the Symfony DSN like:
     *
     *   $scheme = $config['scheme'] ?? null;
     *   if (!$scheme) {
     *       $scheme = $config['encryption'] === 'tls'
     *           ? ($port == 465 ? 'smtps' : 'smtp')
     *           : '';   // ← empty string when encryption = 'ssl'
     *   }
     *
     * When encryption='ssl', scheme becomes '' (empty), and Symfony Mailer's
     * DsnParser then misidentifies the HOST as the scheme, producing:
     *   "mail.gratus.com.ng" scheme is not supported
     *
     * FIX: Set $config['scheme'] = 'smtps' directly. Laravel reads this key
     * first and skips the broken auto-detection entirely.
     *
     * Also update your .env: change MAIL_ENCRYPTION=ssl → MAIL_ENCRYPTION=tls
     * (cPanel accepts tls on port 465; tls+465 also resolves to smtps in
     * Laravel's logic as a second layer of protection).
     */
    private function forceCpanelMailConfig(): void
    {
        if (config('mail.default') !== 'smtp') {
            return;
        }

        $port       = (int) config('mail.mailers.smtp.port', 465);
        $encryption = strtolower((string) config('mail.mailers.smtp.encryption', 'ssl'));

        // Determine the correct Symfony Mailer scheme:
        //   port 465 OR encryption ssl → smtps (implicit TLS)
        //   port 587 AND encryption tls → smtp  (STARTTLS)
        $scheme = ($port === 465 || $encryption === 'ssl') ? 'smtps' : 'smtp';

        config([
            // THIS is the key Laravel MailManager actually reads —
            // it short-circuits the broken auto-detection when present.
            'mail.mailers.smtp.scheme'  => $scheme,
            'mail.mailers.smtp.timeout' => 30,
        ]);

        // Bust the cached mailer so the corrected scheme is used immediately.
        $this->app->forgetInstance('mail.manager');
        $this->app->forgetInstance('mailer');
    }
}
