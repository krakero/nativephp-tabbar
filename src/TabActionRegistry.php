<?php

namespace Krakero\TabBar;

use Closure;

class TabActionRegistry
{
    /**
     * Registered action callbacks, keyed by action identifier.
     *
     * @var array<string, Closure>
     */
    protected array $callbacks = [];

    /**
     * Register a callback for a given action identifier.
     */
    public function register(string $action, Closure $callback): void
    {
        $this->callbacks[$action] = $callback;
    }

    /**
     * Register multiple callbacks at once.
     *
     * @param  array<string, Closure>  $callbacks
     */
    public function registerMany(array $callbacks): void
    {
        foreach ($callbacks as $action => $callback) {
            $this->register($action, $callback);
        }
    }

    /**
     * Check if a callback is registered for the given action.
     */
    public function has(string $action): bool
    {
        return isset($this->callbacks[$action]);
    }

    /**
     * Execute the callback for the given action, if registered.
     *
     * Returns true if a callback was found and executed, false otherwise.
     */
    public function execute(string $id, string $action): bool
    {
        if (! $this->has($action)) {
            return false;
        }

        call_user_func($this->callbacks[$action], $id, $action);

        return true;
    }

    /**
     * Remove a registered callback.
     */
    public function forget(string $action): void
    {
        unset($this->callbacks[$action]);
    }

    /**
     * Remove all registered callbacks.
     */
    public function flush(): void
    {
        $this->callbacks = [];
    }

    /**
     * Get all registered action identifiers.
     *
     * @return string[]
     */
    public function actions(): array
    {
        return array_keys($this->callbacks);
    }
}
