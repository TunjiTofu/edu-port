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
        $encryption = strtolower((string) config('mail.mailers.smtp.encryption', 'tls'));

        // ── Scheme ────────────────────────────────────────────────────────
        // smtps = implicit TLS on port 465
        // smtp  = STARTTLS on port 587 (more compatible with shared hosting)
        //
        // RECOMMENDED .env for cPanel:
        //   MAIL_PORT=587
        //   MAIL_ENCRYPTION=tls
        //
        // This avoids the OpenSSL "wrong version number" error that occurs
        // when PHP's stream wrapper negotiates SSL on port 465 with a server
        // that expects a different TLS handshake order.
        $scheme = ($port === 465 || $encryption === 'ssl') ? 'smtps' : 'smtp';

        config([
            'mail.mailers.smtp.scheme'  => $scheme,
            // Short timeout — fail fast so a dead mail server does not hang
            // the request for 60s. Non-blocking dispatch makes this safe.
            'mail.mailers.smtp.timeout' => 15,
            // ── SSL stream context ────────────────────────────────────────
            // The "wrong version number" OpenSSL error happens when PHP tries
            // an old TLS record version that the server rejects.
            // These options force TLS 1.2+ and relax peer verification so
            // a self-signed or mismatched cPanel cert does not block delivery.
            //
            // NOTE: verify_peer should be re-enabled once you have a valid
            // cert on the mail host. For production with a real cert, set
            // MAIL_VERIFY_PEER=true in .env and read it here.
            'mail.mailers.smtp.stream'  => [
                'ssl' => [
                    'allow_self_signed'      => true,
                    'verify_peer'            => false,
                    'verify_peer_name'       => false,
                    'crypto_method'          => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
                        | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
                ],
            ],
        ]);

        $this->app->forgetInstance('mail.manager');
        $this->app->forgetInstance('mailer');
    }
}
