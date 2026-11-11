<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class BookingAlreadyCancelledException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This booking has already been cancelled.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
