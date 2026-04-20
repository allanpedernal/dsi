<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consistent JSON envelope used by both web AJAX endpoints and the API.
 */
class ApiResponse
{
    /** Build a 200 OK JSON envelope wrapping the given data. */
    public static function ok(mixed $data = null, ?string $message = null, int $status = Response::HTTP_OK): JsonResponse
    {
        return self::respond(true, $data, $message, $status);
    }

    /** Build a 201 Created JSON envelope wrapping the new resource. */
    public static function created(mixed $data = null, ?string $message = 'Created'): JsonResponse
    {
        return self::respond(true, $data, $message, Response::HTTP_CREATED);
    }

    /** Build a 204 No Content JSON envelope. */
    public static function noContent(?string $message = null): JsonResponse
    {
        return self::respond(true, null, $message, Response::HTTP_NO_CONTENT);
    }

    /** Build an error JSON envelope with an optional validation-error bag. */
    public static function error(string $message, int $status = Response::HTTP_BAD_REQUEST, mixed $errors = null): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    private static function respond(bool $success, mixed $data, ?string $message, int $status): JsonResponse
    {
        if ($data instanceof ResourceCollection) {
            $payload = $data->response()->getData(true);
            $payload = array_merge(['success' => $success, 'message' => $message], $payload);

            return new JsonResponse($payload, $status);
        }

        if ($data instanceof JsonResource) {
            $data = $data->resolve();
        }

        return new JsonResponse([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
