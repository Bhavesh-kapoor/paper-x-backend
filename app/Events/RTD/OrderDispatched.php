<?php

namespace App\Events\RTD;

use App\Models\RtdOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderDispatched
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly RtdOrder $order)
    {
    }
}
