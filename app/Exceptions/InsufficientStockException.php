<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Raised when a sale requests more units of a product than are currently in stock.
 */
class InsufficientStockException extends Exception
{
    public function __construct(string $productName, int $requested, int $available)
    {
        parent::__construct(
            "Insufficient stock for {$productName}: requested {$requested}, only {$available} available.",
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    /** Render as a 422 JSON error payload using the shared ApiResponse envelope. */
    public function render(): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), $this->getCode());
    }
}
