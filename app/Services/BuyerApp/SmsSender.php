<?php

namespace App\Services\BuyerApp;

interface SmsSender
{
    /** Send one SMS. Return true only if the gateway accepted it. Must not throw. */
    public function send(string $phone, string $text): bool;
}
