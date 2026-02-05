# NativePHP TabBar

A NativePHP v3 plugin that renders a **native tab bar** on iOS (`UITabBar`) and Android (`BottomNavigationView`), configured entirely from PHP using a fluent, Eloquent-style API.

## Features

- **Fluent PHP API** — Define tabs with a chainable builder pattern
- **URL & Action tabs** — Navigate to URLs or trigger callbacks when tapped
- **Action callbacks** — Pass closures directly to action tabs, executed automatically
- **Badges** — Numeric badges with custom colors
- **Dynamic show/hide** — Programmatically show or hide the tab bar
- **Runtime updates** — Add, remove, or modify tabs on the fly
- **Full theming** — Custom colors for active/inactive states, badges, backgrounds
- **Dark mode** — Separate light and dark mode color configurations
- **System & custom icons** — SF Symbols (iOS), Material Icons (Android), or bundled assets
- **Auto-configure on boot** — Define tabs once in your service provider

## Installation

```bash
composer require krakero/nativephp-tabbar
```

Register the plugin:

```bash
php artisan native:plugin:register krakero/nativephp-tabbar
```

## Quick Start

### 1. Create Your Tab Bar Provider

```php
// app/Providers/NativeTabBarProvider.php

namespace App\Providers;

use Krakero\TabBar\TabBarServiceProvider;
use Krakero\TabBar\TabItem;
use Krakero\TabBar\TabBarStyle;

class NativeTabBarProvider extends TabBarServiceProvider
{
    public function tabs(): array
    {
        return [
            TabItem::make('home')
                ->label('Home')
                ->icon('house')
                ->url('/dashboard')
                ->active(),

            TabItem::make('search')
                ->label('Search')
                ->icon('magnifyingglass')
                ->url('/search'),

            TabItem::make('create')
                ->label('Create')
                ->icon('plus.circle')
                ->action(fn ($id, $action) => redirect('/posts/create')),

            TabItem::make('inbox')
                ->label('Inbox')
                ->icon('bell')
                ->url('/notifications')
                ->badge(5),

            TabItem::make('profile')
                ->label('Profile')
                ->icon('person.circle')
                ->url('/profile'),
        ];
    }

    public function tabStyle(): TabBarStyle
    {
        return TabBarStyle::make()
            ->backgroundColor('#FFFFFF')
            ->activeColor('#007AFF')
            ->inactiveColor('#8E8E93')
            ->badgeColor('#FF3B30')
            ->badgeTextColor('#FFFFFF')
            ->darkBackgroundColor('#1C1C1E')
            ->darkActiveColor('#0A84FF')
            ->darkInactiveColor('#8E8E93');
    }
}
```

### 2. Register the Provider

Add to your `bootstrap/providers.php`:

```php
return [
    // ...
    App\Providers\NativeTabBarProvider::class,
];
```

## Dynamic Tab Visibility

Tabs can have dynamic visibility that changes based on application state. Instead of passing a boolean to `->visible()`, you can pass a closure that returns a boolean. This closure is evaluated:
- When tabs are initially configured
- When tabs are updated via `TabBar::update()`
- When a tab is selected (automatically re-evaluates all visibility)
- When you call `TabBar::refresh()` manually

### Examples

```php
// In your NativeTabBarProvider
public function tabs(): array
{
    return [
        TabItem::make('home')
            ->label('Home')
            ->icon('house')
            ->url('/dashboard')
            ->visible(true), // Static visibility

        TabItem::make('admin')
            ->label('Admin')
            ->icon('gear')
            ->url('/admin')
            ->visible(function() {
                // Only show for admin users
                return auth()->user()?->isAdmin() ?? false;
            }),

        TabItem::make('posts')
            ->label('Posts')
            ->icon('document')
            ->url('/posts')
            ->visible(function() {
                // Hide when already viewing a post
                return ! request()->is('posts/*');
            }),

        TabItem::make('debug')
            ->label('Debug')
            ->icon('bug')
            ->url('/debug')
            ->visible(fn() => app()->environment('local')),
    ];
}
```

### Manual Refresh

If you need to refresh tab visibility after a state change (like user login/logout), you can manually trigger a refresh:

```php
use Krakero\TabBar\Facades\TabBar;

// After user login
auth()->login($user);
TabBar::refresh(); // Re-evaluates all visibility callbacks

// After changing application state
session(['show_admin_tools' => true]);
TabBar::refresh();
```

The tabs automatically refresh when users switch between tabs, so visibility callbacks will be re-evaluated as users navigate through your app.

## Action Tabs

Action tabs don't navigate — they execute code when tapped. There are three ways to define them:

### Closure Only (simplest)

Pass a closure directly. The tab ID is used as the action name automatically:

```php
TabItem::make('create')
    ->label('Create')
    ->icon('plus.circle')
    ->action(fn ($id, $action) => redirect('/posts/create')),
```

### Named Action + Closure

Give the action an explicit name alongside the closure:

```php
TabItem::make('create')
    ->label('Create')
    ->icon('plus.circle')
    ->action('create-post', function ($id, $action) {
        session()->flash('modal', 'create-post');
        redirect('/dashboard');
    }),
```

### String Only (manual event handling)

Omit the closure and handle the event yourself in a Livewire component:

```php
TabItem::make('create')
    ->label('Create')
    ->icon('plus.circle')
    ->action('create-post'),
```

```php
// In your Livewire component
use Krakero\TabBar\Events\TabActionTriggered;
use Native\Mobile\Attributes\OnNative;

#[OnNative(TabActionTriggered::class)]
public function handleTabAction($id, $action)
{
    match ($action) {
        'create-post' => $this->dispatch('open-create-modal'),
        default => null,
    };
}
```

### Combining Both

When you provide a closure, it runs automatically **and** the event still fires. This means you can use a closure for the primary behavior and still catch the event in Livewire for secondary concerns like analytics or UI state updates:

```php
// In your provider — primary behavior
TabItem::make('create')
    ->label('Create')
    ->icon('plus.circle')
    ->action('create-post', fn ($id, $action) => redirect('/posts/create')),
```

```php
// In your Livewire component — secondary behavior (also fires!)
#[OnNative(TabActionTriggered::class)]
public function handleTabAction($id, $action)
{
    // Track analytics, update UI state, etc.
    $this->activeSection = $action;
}
```

## API Reference

### TabItem

| Method | Description |
|---|---|
| `TabItem::make(string $id)` | Create a new tab with a unique identifier |
| `->label(string $label)` | Set the display label |
| `->icon(string $name)` | Set a system icon (SF Symbol / Material) |
| `->customIcon(string $path)` | Set a custom bundled icon |
| `->activeIcon(string $name)` | Set a different system icon for the active state |
| `->activeCustomIcon(string $path)` | Set a different custom icon for the active state |
| `->url(string $url)` | Navigate the webview when tapped |
| `->action(string\|Closure $action, ?Closure $callback)` | Trigger an action when tapped (see above) |
| `->badge(?int $count)` | Set a numeric badge (null to clear) |
| `->badgeColor(string $hex)` | Set the badge background color |
| `->visible(bool\|Closure $visible)` | Show or hide this tab (can use a callback for dynamic visibility) |
| `->active()` | Mark as the initially selected tab |

### TabBar Facade

```php
use Krakero\TabBar\Facades\TabBar;

// Programmatically switch tabs
TabBar::setActive('profile');

// Update badges
TabBar::setBadge('inbox', 12);
TabBar::setBadge('inbox', null); // clear

// Show/hide
TabBar::hide();
TabBar::show();

// Refresh tabs (re-evaluates visibility callbacks)
TabBar::refresh();

// Full runtime reconfiguration (re-registers callbacks too)
TabBar::update([
    TabItem::make('home')->label('Home')->icon('house')->url('/'),
    TabItem::make('settings')->label('Settings')->icon('gear')->url('/settings'),
]);
```

### TabBarStyle

| Method | Description |
|---|---|
| `TabBarStyle::make()` | Create a new style instance |
| `->backgroundColor(string $hex)` | Tab bar background |
| `->activeColor(string $hex)` | Selected tab icon/text color |
| `->inactiveColor(string $hex)` | Unselected tab icon/text color |
| `->badgeColor(string $hex)` | Badge background color |
| `->badgeTextColor(string $hex)` | Badge text color |
| `->borderColor(string $hex)` | Top border color |
| `->borderWidth(float $width)` | Top border width |
| `->translucent(bool)` | Translucent background (iOS only) |
| `->elevation(float $dp)` | Shadow elevation (Android only) |
| `->dark*()` | All color methods have dark mode variants (e.g. `darkBackgroundColor`) |

### Events

| Event | Payload | When |
|---|---|---|
| `TabSelected` | `$id`, `$url`, `$index` | Any tab is tapped |
| `TabActionTriggered` | `$id`, `$action` | An action-type tab is tapped |

Both events fire regardless of whether a callback is registered. Callbacks run first, then the event propagates to any additional listeners.

### JavaScript (SPA)

```js
import { setActive, setBadge, show, hide } from './vendor/krakero/nativephp-tabbar/resources/js/tabbar.js';

await setActive('profile');
await setBadge('inbox', 3);
await hide();
await show();
```

## Icon Mapping

When using system icons, use SF Symbol names in PHP. On Android, they are automatically mapped to Material equivalents:

| PHP (SF Symbol) | Android Equivalent |
|---|---|
| `house` | Home icon |
| `magnifyingglass` | Search icon |
| `plus.circle` | Add icon |
| `bell` | Notification icon |
| `person.circle` | Person icon |
| `gear` | Settings icon |
| `star` / `star.fill` | Star icon |
| `camera` | Camera icon |
| `map` | Map icon |
| `envelope` | Email icon |

For icons not in the mapping, use `->customIcon()` with bundled assets.

## Constraints

- **Maximum 5 tabs** — Enforced by validation. Both iOS `UITabBar` and Android `BottomNavigationView` are designed for 3-5 items.
- **Action callbacks run server-side** — Closures execute in PHP when the native event is received. They cannot directly manipulate the DOM.
- **Tab IDs must be unique** — Validated at configuration time.
- **One active tab** — Only one tab can be marked as `->active()` at a time.

## License

MIT
