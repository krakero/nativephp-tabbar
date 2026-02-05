<?php

namespace Krakero\TabBar\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TabSelected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $id,
        public ?string $url = null,
        public int $index = 0,
    ) {}
}
