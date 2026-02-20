<?php

namespace App\Events\RTD;

use App\Models\RtdOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly RtdOrder $order)
    {
    }
}
