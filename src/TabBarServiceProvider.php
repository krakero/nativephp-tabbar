<?php

namespace Krakero\TabBar;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Krakero\TabBar\Events\TabActionTriggered;
use Krakero\TabBar\Events\TabSelected;

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
        $this->registerTabSelectedListener();

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
            app(TabActionRegistry::class)->execute($event->id, $event->action);
        });
    }

    /**
     * Register the event listener that refreshes tabs when a tab is selected.
     *
     * This ensures visibility callbacks are re-evaluated when the user
     * navigates between tabs, allowing dynamic visibility based on the
     * current application state (e.g., route, user permissions, etc.).
     */
    protected function registerTabSelectedListener(): void
    {
        Event::listen(TabSelected::class, function (TabSelected $event) {
            app(TabBar::class)->refresh();
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
}
