<?php

namespace Krakero\TabBar\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TabActionTriggered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $id,
        public string $action,
    ) {}
}
