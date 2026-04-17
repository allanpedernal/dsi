<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InsufficientStockException extends Exception
{
    public function __construct(string $productName, int $requested, int $available)
    {
        parent::__construct(
            "Insufficient stock for {$productName}: requested {$requested}, only {$available} available.",
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), $this->getCode());
    }
}
