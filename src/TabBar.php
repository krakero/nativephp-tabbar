<?php

namespace Krakero\TabBar;

use InvalidArgumentException;

class TabBar
{
    public const MAX_TABS = 5;

    protected TabActionRegistry $registry;

    public function __construct(TabActionRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Configure the tab bar with a full set of tabs and optional styling.
     * This is typically called automatically on app boot by the service provider.
     *
     * Any tabs with action callbacks will have their closures registered
     * in the TabActionRegistry for automatic execution when tapped.
     *
     * @param  TabItem[]  $tabs
     *
     * @throws InvalidArgumentException
     */
    public function configure(array $tabs, ?TabBarStyle $style = null): mixed
    {
        $this->validateTabs($tabs);
        $this->registerCallbacks($tabs);

        $config = $this->buildConfig($tabs, $style);

        return $this->call('TabBar.Configure', $config);
    }

    /**
     * Replace the tab bar configuration at runtime.
     *
     * Flushes all previously registered callbacks and re-registers
     * from the new tab set.
     *
     * @param  TabItem[]  $tabs
     *
     * @throws InvalidArgumentException
     */
    public function update(array $tabs, ?TabBarStyle $style = null): mixed
    {
        $this->validateTabs($tabs);

        // Flush old callbacks and register new ones
        $this->registry->flush();
        $this->registerCallbacks($tabs);

        $config = $this->buildConfig($tabs, $style);

        return $this->call('TabBar.Update', $config);
    }

    /**
     * Programmatically switch the active tab by its identifier.
     */
    public function setActive(string $tabId): mixed
    {
        return $this->call('TabBar.SetActive', ['id' => $tabId]);
    }

    /**
     * Set or clear a numeric badge on a specific tab.
     * Pass null for $count to clear the badge.
     */
    public function setBadge(string $tabId, ?int $count): mixed
    {
        return $this->call('TabBar.SetBadge', [
            'id' => $tabId,
            'count' => $count,
        ]);
    }

    /**
     * Show the tab bar (if previously hidden).
     */
    public function show(): mixed
    {
        return $this->call('TabBar.Show', []);
    }

    /**
     * Hide the tab bar.
     */
    public function hide(): mixed
    {
        return $this->call('TabBar.Hide', []);
    }

    /**
     * Get the action registry instance.
     */
    public function getRegistry(): TabActionRegistry
    {
        return $this->registry;
    }

    /**
     * Extract action callbacks from tabs and register them in the registry.
     *
     * @param  TabItem[]  $tabs
     */
    protected function registerCallbacks(array $tabs): void
    {
        foreach ($tabs as $tab) {
            if ($tab->hasActionCallback()) {
                $this->registry->register(
                    $tab->getActionName(),
                    $tab->getActionCallback(),
                );
            }
        }
    }

    /**
     * Build the configuration payload for the native bridge.
     *
     * @param  TabItem[]  $tabs
     */
    protected function buildConfig(array $tabs, ?TabBarStyle $style = null): array
    {
        $activeTabId = null;

        foreach ($tabs as $tab) {
            if ($tab->isActive()) {
                $activeTabId = $tab->getId();
                break;
            }
        }

        $config = [
            'tabs' => array_map(fn (TabItem $tab) => $tab->toArray(), array_values($tabs)),
        ];

        if ($activeTabId !== null) {
            $config['active_tab'] = $activeTabId;
        }

        if ($style !== null) {
            $config['style'] = $style->toArray();
        }

        return $config;
    }

    /**
     * Validate the tab array, enforcing the max tab limit and unique IDs.
     *
     * @param  TabItem[]  $tabs
     *
     * @throws InvalidArgumentException
     */
    protected function validateTabs(array $tabs): void
    {
        if (count($tabs) > self::MAX_TABS) {
            throw new InvalidArgumentException(
                sprintf(
                    'TabBar supports a maximum of %d tabs. %d provided. iOS UITabBar and Android BottomNavigationView do not support more than %d items.',
                    self::MAX_TABS,
                    count($tabs),
                    self::MAX_TABS,
                )
            );
        }

        if (count($tabs) === 0) {
            throw new InvalidArgumentException('TabBar requires at least one tab.');
        }

        $ids = array_map(fn (TabItem $tab) => $tab->getId(), $tabs);
        $duplicates = array_diff_assoc($ids, array_unique($ids));

        if (! empty($duplicates)) {
            throw new InvalidArgumentException(
                sprintf('Duplicate tab IDs found: %s. Each tab must have a unique ID.', implode(', ', array_unique($duplicates)))
            );
        }

        $activeCount = count(array_filter($tabs, fn (TabItem $tab) => $tab->isActive()));

        if ($activeCount > 1) {
            throw new InvalidArgumentException('Only one tab can be marked as active. Found '.$activeCount.' active tabs.');
        }
    }

    /**
     * Call a native bridge function.
     */
    protected function call(string $method, array $params): mixed
    {
        if (function_exists('nativephp_call')) {
            $result = nativephp_call($method, json_encode($params));

            return json_decode($result)?->data;
        }

        return null;
    }
}
