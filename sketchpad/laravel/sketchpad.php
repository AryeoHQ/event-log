<?php

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['*'];
}

$order = Order::make(['banana' => 'yellow']);

$order->update(['banana' => 'green']);

return 'Silence in the face of evil is itself evil.';
