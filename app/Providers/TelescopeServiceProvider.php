<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Telescope::night();

        $this->hideSensitiveRequestDetails();
        /**
         * Eğer isteğin tipi "Request" ise isteğin durum koduna göre gruplamış
         * oluyoruz.
         */
        Telescope::tag(function (IncomingEntry $entry) {
            return $entry->type === 'request'
                ? ['status:'.$entry->content['response_status']]
                : [];
        });

        Telescope::filter(function (IncomingEntry $entry) {
            /**
             * Eğer uygulama local durumdaysa bir filtreleme uygulanmaz
            */
           if ($this->app->environment('local')) {
                return true;
            }

            /**
            * Değilse
            * Hatalı olarn İstekler, Planlanan  Eylemler, Tamamlanmayan  İşler
            * vb. durumlar telescope üzerinde görüntülenmezler.
             */
            return $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     *
     * @return void
     */
    protected function hideSensitiveRequestDetails()
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewTelescope', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
