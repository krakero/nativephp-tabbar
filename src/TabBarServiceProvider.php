<?php

namespace Krakero\TabBar;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Krakero\TabBar\Events\TabActionTriggered;

class TabBarServiceProvider extends ServiceProvider
{
    /**
     * Register the tab bar singleton, action registry, and facade alias.
     */
    public function register(): void
    {
        $this->app->singleton(TabActionRegistry::class);

        $this->app->singleton(TabBar::class, function ($app) {
            return new TabBar($app->make(TabActionRegistry::class));
        });

        $this->app->alias(TabBar::class, 'tabbar');
    }

    /**
     * Boot the service provider.
     *
     * - Registers an event listener that automatically executes action
     *   callbacks from the registry when TabActionTriggered fires.
     * - If running inside NativePHP and the tabs() method has been defined
     *   (typically by the user extending this provider), the tab bar is
     *   automatically configured on app boot.
     */
    public function boot(): void
    {
        $this->registerActionListener();

        if (! function_exists('nativephp_call')) {
            return;
        }

        if (! method_exists($this, 'tabs')) {
            return;
        }

        $tabs = $this->tabs();
        $style = method_exists($this, 'tabStyle') ? $this->tabStyle() : null;

        app(TabBar::class)->configure($tabs, $style);
    }

    /**
     * Register the event listener that auto-executes action callbacks.
     *
     * This listens for TabActionTriggered events dispatched from native code
     * and executes any registered closure for the matching action. The event
     * continues to propagate normally, so users can also listen for it via
     * Livewire's #[OnNative] attribute or any other Laravel event listener.
     */
    protected function registerActionListener(): void
    {
        Event::listen(TabActionTriggered::class, function (TabActionTriggered $event) {
            $registry = app(TabActionRegistry::class);
            $registry->execute($event->id, $event->action);

            // Return void (not false) so the event continues propagating
            // to any additional listeners, including Livewire #[OnNative].
        });
    }

    /**
     * Define the tab items for the tab bar.
     *
     * Override this method in your own service provider to configure tabs.
     *
     * Example:
     *
     *     public function tabs(): array
     *     {
     *         return [
     *             TabItem::make('home')
     *                 ->label('Home')
     *                 ->icon('house')
     *                 ->url('/dashboard')
     *                 ->active(),
     *
     *             TabItem::make('create')
     *                 ->label('Create')
     *                 ->icon('plus.circle')
     *                 ->action(fn ($id, $action) => redirect('/posts/create')),
     *
     *             TabItem::make('profile')
     *                 ->label('Profile')
     *                 ->icon('person.circle')
     *                 ->url('/profile'),
     *         ];
     *     }
     *
     * @return TabItem[]
     */
    // public function tabs(): array
    // {
    //     return [];
    // }

    /**
     * Define the tab bar style / theming.
     *
     * Override this method in your own service provider to customize appearance.
     *
     * Example:
     *
     *     public function tabStyle(): TabBarStyle
     *     {
     *         return TabBarStyle::make()
     *             ->backgroundColor('#FFFFFF')
     *             ->activeColor('#007AFF')
     *             ->inactiveColor('#8E8E93')
     *             ->darkBackgroundColor('#1C1C1E')
     *             ->darkActiveColor('#0A84FF')
     *             ->darkInactiveColor('#8E8E93');
     *     }
     */
    // public function tabStyle(): TabBarStyle
    // {
    //     return TabBarStyle::make();
    // }
}
