<?php

namespace Aura\Base\Tests;

use Aura\Base\ConditionalLogic;
use Aura\Base\Facades\Aura;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Contracts\Http\Kernel;

class BrowserTestCase extends TestCase
{
    protected bool $enableVite = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Process-static field/condition caches survive both the per-test app
        // refresh and RefreshDatabase. The Feature suite clears them in its
        // afterEach (via Aura::flushState); the browser suite has no such hook,
        // so a stale entry leaks across tests. This bites hardest for
        // conditional_logic: shouldDisplayField() memoizes visibility keyed by
        // Auth::id(), and RefreshDatabase reuses ids — so a field a prior test
        // hid for a non-Global-Admin at id N stays hidden for THIS test's
        // Global Admin who happens to reuse id N. Clear them up front.
        ConditionalLogic::clearConditionsCache();
        Resource::flushFieldCache();

        $this->app[Kernel::class]
            ->prependMiddleware(Browser\Support\ParseMultipartBody::class);

        // Browser tests exercise real resource pages; boot-time route
        // generation has already run, so add this resource's routes now.
        // Re-capture the baseline afterwards: Queue::after triggers
        // Aura::flushState(), which would otherwise drop the registration
        // as soon as any sync job (e.g. thumbnail generation) runs.
        Aura::registerResources([Resources\GalleryPage::class]);
        Aura::registerRoutes('gallery-page');
        Aura::captureBaselineState();

        $this->serveBuiltAssets();
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        // Real browsers make many HTTP requests; the session must survive
        // between them, which the array driver cannot do.
        $app['config']->set('session.driver', 'file');

        // A published Aura app points the auth provider at the Aura user model.
        // Feature tests lean on actingAs() (an in-memory instance), but the real
        // login form authenticates through the guard's provider, which then
        // resolves the user from the session on every later request. Without this
        // the guard would hand back a bare Illuminate\Foundation\Auth\User that
        // lacks Aura's methods (isSuperAdmin, permissions, …).
        $app['config']->set('auth.providers.users.model', User::class);

        // Livewire forces this disk name for temporary uploads while
        // runningUnitTests(), but only component tests fake it — real
        // browser uploads need it to exist.
        $app['config']->set('filesystems.disks.tmp-for-tests', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/tmp-for-tests'),
        ]);

    }

    /**
     * Mirror what `artisan vendor:publish --tag=aura-assets` does, via
     * symlinks into the isolated testbench public path, so the browser
     * gets the package's real CSS/JS.
     *
     * Must be race-safe under parallel workers: a peer may create the
     * vendor/aura directory before the symlinks exist. Returning early on
     * is_dir() alone leaves tippy/window.aura undefined and breaks popovers.
     */
    private function serveBuiltAssets(): void
    {
        $base = dirname(__DIR__).'/resources';
        $target = public_path('vendor/aura');

        if (! is_dir($base.'/dist/assets')) {
            throw new \RuntimeException(
                'Browser tests need built frontend assets at resources/dist. Run `npm run build`.'
            );
        }

        @mkdir($target, 0755, true);

        $links = [
            'assets' => $base.'/dist/assets',
            'manifest.json' => $base.'/dist/manifest.json',
            'libs' => $base.'/libs',
            'public' => $base.'/public',
        ];

        foreach ($links as $name => $source) {
            $link = $target.DIRECTORY_SEPARATOR.$name;

            if (file_exists($link) || is_link($link)) {
                continue;
            }

            @symlink($source, $link);
        }
    }
}
