<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

/**
 * @mixin \Illuminate\Contracts\Routing\ResponseFactory
 */
class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /** @var \Illuminate\Routing\ResponseFactory $response */

        // Macro for success response
        Response::macro('success', function ($message = null, $data = null, $meta = null, $statusCode = 200) {
            // Check if data is a paginated resource
            if ($data instanceof \Illuminate\Http\Resources\Json\ResourceCollection && method_exists($data->resource, 'toArray')) {
                // Extract pagination meta if available
                $paginationMeta = $data->resource->toArray();
                $meta = array_merge($meta ?? [], [
                    'current_page' => $paginationMeta['current_page'] ?? 1,
                    'last_page' => $paginationMeta['last_page'] ?? 1,
                    'per_page' => $paginationMeta['per_page'] ?? count($data),
                    'total' => $paginationMeta['total'] ?? count($data),
                ]);
            }

            $response = array_filter([
                'success' => true,
                'message' => $message ? __($message) : null,
                'data' => $data,
                'meta' => $meta
            ]);

            return response()->json($response,  in_array($statusCode, [200, 201]) ? $statusCode : 404);
        });

        // Macro for error response
        Response::macro('error', function ($message = null, $errors = null, $statusCode = 400) {
            $response = [
                'success' => false,
                'message' => $message ? __($message) : null,
            ];

            // Include errors if available
            if ($errors) {
                $response['errors'] = $errors;
            }

            // Map common exception codes to appropriate HTTP status codes
            if ($statusCode <= 0 || $statusCode >= 600) {
                // Handle model not found exceptions (usually code 0)
                if ($statusCode == 0) {
                    $statusCode = 404;
                } else {
                    $statusCode = 500;
                }
            }

            return response()->json($response, $statusCode);
        });
    }
}
