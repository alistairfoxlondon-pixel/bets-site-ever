<?php

namespace App\Providers;

use App\Models\Audit;
use App\Models\ChatConversation;
use App\Models\ContactMessage;
use App\Models\ContentDocument;
use App\Models\Notification;
use App\Models\Setting;
use App\Services\Ai\AiClient;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::createAssetPathsUsing(fn (string $path, $secure = null) => '/'.ltrim($path, '/'));

        $this->configureGoogleAuth();
        $this->shareLayoutData();
        $this->shareSettings();
    }

    protected function configureGoogleAuth(): void
    {
        try {
            if (! $this->settingsTableReady()) {
                return;
            }

            $clientId = (string) (Setting::get('auth.google_client_id') ?: config('services.google.client_id'));
            $clientSecret = (string) (Setting::get('auth.google_client_secret') ?: config('services.google.client_secret'));

            if ($clientId !== '') {
                config([
                    'services.google.client_id' => $clientId,
                    'services.google.client_secret' => $clientSecret ?: config('services.google.client_secret'),
                ]);
            }
        } catch (Throwable) {
            // Installer and package discovery run before the database exists.
        }
    }

    protected function settingsTableReady(): bool
    {
        static $ready = null;

        if ($ready === null) {
            try {
                $ready = Schema::hasTable('settings');
            } catch (Throwable) {
                $ready = false;
            }
        }

        return $ready;
    }

    /**
     * Counts shown in the sidebars of the product and the control panel.
     */
    protected function shareLayoutData(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            $view->with([
                'sidebarCounts' => $user ? [
                    'audits' => Audit::query()->where('user_id', $user->id)->count(),
                    'documents' => ContentDocument::query()->where('user_id', $user->id)->count(),
                ] : [],
                'unreadNotifications' => $user
                    ? Notification::query()->where('user_id', $user->id)->whereNull('read_at')->count()
                    : 0,
            ]);
        });

        View::composer('layouts.admin', function ($view) {
            $view->with([
                'openChats' => ChatConversation::query()->where('status', 'open')->count(),
                'newMessages' => ContactMessage::query()->where('status', 'new')->count(),
            ]);
        });
    }

    /**
     * A few settings are used on nearly every page, so read them once.
     */
    protected function shareSettings(): void
    {
        View::composer('*', function ($view) {
            $support = config('adready.defaults.support_email');
            $aiReady = false;

            if ($this->settingsTableReady()) {
                try {
                    $support = Setting::get('support_email', $support);
                    $aiReady = AiClient::make()->hasKey();
                } catch (Throwable) {
                    // Keep the compiled defaults when the database is not ready.
                }
            }

            $view->with([
                'siteSupportEmail' => $support,
                'aiReady' => $aiReady,
            ]);
        });
    }
}
