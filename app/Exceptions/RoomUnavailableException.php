<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class RoomUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The room is already booked for some or all of the requested dates.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
