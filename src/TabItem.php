<?php

namespace Krakero\TabBar;

use Closure;
use Krakero\TabBar\Enums\TabIconType;
use Krakero\TabBar\Enums\TabType;

class TabItem
{
    protected string $id;

    protected string $label = '';

    protected string $icon = '';

    protected TabIconType $iconType = TabIconType::System;

    protected ?string $activeIcon = null;

    protected ?TabIconType $activeIconType = null;

    protected TabType $type = TabType::Url;

    protected ?string $url = null;

    protected ?string $actionName = null;

    protected ?Closure $actionCallback = null;

    protected ?int $badge = null;

    protected ?string $badgeColor = null;

    protected bool|Closure $visible = true;

    protected bool $isActive = false;

    /**
     * Create a new tab item with the given identifier.
     */
    public static function make(string $id): static
    {
        $instance = new static();
        $instance->id = $id;

        return $instance;
    }

    /**
     * Set the display label for this tab.
     */
    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set a system icon by name (SF Symbol on iOS, Material Icon on Android).
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        $this->iconType = TabIconType::System;

        return $this;
    }

    /**
     * Set a custom icon from a bundled image asset path.
     */
    public function customIcon(string $path): static
    {
        $this->icon = $path;
        $this->iconType = TabIconType::Custom;

        return $this;
    }

    /**
     * Set a system icon to display when this tab is active.
     */
    public function activeIcon(string $icon): static
    {
        $this->activeIcon = $icon;
        $this->activeIconType = TabIconType::System;

        return $this;
    }

    /**
     * Set a custom icon to display when this tab is active.
     */
    public function activeCustomIcon(string $path): static
    {
        $this->activeIcon = $path;
        $this->activeIconType = TabIconType::Custom;

        return $this;
    }

    /**
     * Set this tab to navigate to a URL when tapped.
     */
    public function url(string $url): static
    {
        $this->url = $url;
        $this->type = TabType::Url;

        return $this;
    }

    /**
     * Set this tab to trigger an action when tapped.
     *
     * Supports three signatures:
     *
     *   ->action('create-post')                              // String only — handle via #[OnNative] event listener
     *   ->action(fn ($id, $action) => doSomething())         // Closure only — uses tab ID as action name
     *   ->action('create-post', fn ($id, $action) => ...)    // Named action with callback
     *
     * When a closure is provided, it is automatically registered and executed
     * when the tab is tapped. The event is still dispatched, so #[OnNative]
     * listeners will also fire.
     *
     * The closure receives two arguments: $id (tab ID) and $action (action name).
     */
    public function action(string|Closure $action, ?Closure $callback = null): static
    {
        $this->type = TabType::Action;

        if ($action instanceof Closure) {
            // ->action(fn () => ...)
            $this->actionName = $this->id;
            $this->actionCallback = $action;
        } elseif ($callback !== null) {
            // ->action('name', fn () => ...)
            $this->actionName = $action;
            $this->actionCallback = $callback;
        } else {
            // ->action('name')
            $this->actionName = $action;
            $this->actionCallback = null;
        }

        return $this;
    }

    /**
     * Set a numeric badge on this tab. Pass null to clear.
     */
    public function badge(?int $count): static
    {
        $this->badge = $count;

        return $this;
    }

    /**
     * Set the badge background color as a hex string (e.g. '#FF3B30').
     */
    public function badgeColor(string $hex): static
    {
        $this->badgeColor = $hex;

        return $this;
    }

    /**
     * Set whether this tab is visible.
     *
     * Can accept either a boolean or a closure that returns a boolean.
     * The closure will be evaluated each time the tab configuration is built.
     */
    public function visible(bool|Closure $visible = true): static
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Mark this tab as the initially active tab on boot.
     */
    public function active(bool $active = true): static
    {
        $this->isActive = $active;

        return $this;
    }

    /**
     * Check whether this tab is marked as the initially active tab.
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Check whether this tab should be visible.
     *
     * If visibility is a closure, it will be evaluated and the result returned.
     * Otherwise, the boolean value is returned directly.
     */
    public function isVisible(): bool
    {
        if ($this->visible instanceof Closure) {
            return (bool) ($this->visible)();
        }

        return (bool) $this->visible;
    }

    /**
     * Get the tab identifier.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the action name (string identifier sent to native).
     */
    public function getActionName(): ?string
    {
        return $this->actionName;
    }

    /**
     * Get the registered action callback, if any.
     */
    public function getActionCallback(): ?Closure
    {
        return $this->actionCallback;
    }

    /**
     * Check if this tab has an action callback registered.
     */
    public function hasActionCallback(): bool
    {
        return $this->actionCallback !== null;
    }

    /**
     * Serialize this tab item to an array for the native bridge.
     *
     * Note: Action closures are NOT included in the serialized output.
     * They are stored separately in the TabActionRegistry.
     * Visibility closures are evaluated at serialization time.
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'label' => $this->label,
            'icon' => $this->icon,
            'icon_type' => $this->iconType->value,
            'active_icon' => $this->activeIcon,
            'active_icon_type' => $this->activeIconType?->value,
            'type' => $this->type->value,
            'url' => $this->url,
            'action' => $this->actionName,
            'badge' => $this->badge,
            'badge_color' => $this->badgeColor,
            'visible' => $this->isVisible(),
            'is_active' => $this->isActive,
        ], fn ($v) => $v !== null);
    }
}
