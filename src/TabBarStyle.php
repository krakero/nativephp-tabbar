<?php

namespace Krakero\TabBar;

class TabBarStyle
{
    // Light mode colors (also used as defaults when dark mode is not specified)
    protected ?string $backgroundColor = null;

    protected ?string $activeColor = null;

    protected ?string $inactiveColor = null;

    protected ?string $badgeColor = null;

    protected ?string $badgeTextColor = null;

    protected ?string $borderColor = null;

    protected ?float $borderWidth = null;

    // Dark mode colors
    protected ?string $darkBackgroundColor = null;

    protected ?string $darkActiveColor = null;

    protected ?string $darkInactiveColor = null;

    protected ?string $darkBadgeColor = null;

    protected ?string $darkBadgeTextColor = null;

    protected ?string $darkBorderColor = null;

    // Platform-specific options
    protected bool $translucent = true;  // iOS only

    protected ?float $elevation = null;  // Android only

    /**
     * Create a new tab bar style instance.
     */
    public static function make(): static
    {
        return new static();
    }

    // -------------------------------------------------------
    // Light mode (default) colors
    // -------------------------------------------------------

    public function backgroundColor(string $hex): static
    {
        $this->backgroundColor = $hex;

        return $this;
    }

    public function activeColor(string $hex): static
    {
        $this->activeColor = $hex;

        return $this;
    }

    public function inactiveColor(string $hex): static
    {
        $this->inactiveColor = $hex;

        return $this;
    }

    public function badgeColor(string $hex): static
    {
        $this->badgeColor = $hex;

        return $this;
    }

    public function badgeTextColor(string $hex): static
    {
        $this->badgeTextColor = $hex;

        return $this;
    }

    public function borderColor(string $hex): static
    {
        $this->borderColor = $hex;

        return $this;
    }

    public function borderWidth(float $width): static
    {
        $this->borderWidth = $width;

        return $this;
    }

    // -------------------------------------------------------
    // Dark mode colors
    // -------------------------------------------------------

    public function darkBackgroundColor(string $hex): static
    {
        $this->darkBackgroundColor = $hex;

        return $this;
    }

    public function darkActiveColor(string $hex): static
    {
        $this->darkActiveColor = $hex;

        return $this;
    }

    public function darkInactiveColor(string $hex): static
    {
        $this->darkInactiveColor = $hex;

        return $this;
    }

    public function darkBadgeColor(string $hex): static
    {
        $this->darkBadgeColor = $hex;

        return $this;
    }

    public function darkBadgeTextColor(string $hex): static
    {
        $this->darkBadgeTextColor = $hex;

        return $this;
    }

    public function darkBorderColor(string $hex): static
    {
        $this->darkBorderColor = $hex;

        return $this;
    }

    // -------------------------------------------------------
    // Platform-specific options
    // -------------------------------------------------------

    /**
     * Set whether the tab bar is translucent (iOS only).
     */
    public function translucent(bool $val = true): static
    {
        $this->translucent = $val;

        return $this;
    }

    /**
     * Set the elevation shadow depth in dp (Android only).
     */
    public function elevation(float $dp): static
    {
        $this->elevation = $dp;

        return $this;
    }

    /**
     * Serialize the style to an array for the native bridge.
     */
    public function toArray(): array
    {
        $style = array_filter([
            'background_color' => $this->backgroundColor,
            'active_color' => $this->activeColor,
            'inactive_color' => $this->inactiveColor,
            'badge_color' => $this->badgeColor,
            'badge_text_color' => $this->badgeTextColor,
            'border_color' => $this->borderColor,
            'border_width' => $this->borderWidth,
            'translucent' => $this->translucent,
            'elevation' => $this->elevation,
        ], fn ($v) => $v !== null);

        $dark = array_filter([
            'background_color' => $this->darkBackgroundColor,
            'active_color' => $this->darkActiveColor,
            'inactive_color' => $this->darkInactiveColor,
            'badge_color' => $this->darkBadgeColor,
            'badge_text_color' => $this->darkBadgeTextColor,
            'border_color' => $this->darkBorderColor,
        ], fn ($v) => $v !== null);

        if (! empty($dark)) {
            $style['dark'] = $dark;
        }

        return $style;
    }
}
