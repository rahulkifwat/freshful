<?php

namespace App\Services\BuyerApp\Payments;

/** Thrown inside the order transaction when the same online payment was already turned into an order (rolls the new order back). */
final class PaymentAlreadyUsed extends \RuntimeException
{
}
