<?php

namespace Krakero\TabBar\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed configure(array $tabs, ?\Krakero\TabBar\TabBarStyle $style = null)
 * @method static mixed update(array $tabs, ?\Krakero\TabBar\TabBarStyle $style = null)
 * @method static mixed setActive(string $tabId)
 * @method static mixed setBadge(string $tabId, ?int $count)
 * @method static mixed show()
 * @method static mixed hide()
 * @method static mixed refresh()
 * @method static \Krakero\TabBar\TabActionRegistry getRegistry()
 *
 * @see \Krakero\TabBar\TabBar
 */
class TabBar extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Krakero\TabBar\TabBar::class;
    }
}
