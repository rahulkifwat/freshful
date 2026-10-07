<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Base controller for all API endpoints (routes/api.php, served under /api).
 *
 * Responses keep the legacy /api/*.php JSON shape ({"result": bool, "message": ...})
 * and always use HTTP 200, so the existing mobile apps keep working unchanged.
 */
abstract class ApiController extends Controller
{
    /** Legacy code ran with date_default_timezone_set('Asia/Kolkata'). */
    protected const TZ = 'Asia/Kolkata';

    protected function respond(array $json): JsonResponse
    {
        return response()->json($json);
    }

    /**
     * Legacy isset() check: every field must be present (empty string counts as present).
     */
    protected function requireFields(Request $request, array $fields, string $message): void
    {
        foreach ($fields as $field) {
            if (! $request->exists($field)) {
                throw new ApiException($message);
            }
        }
    }

    /**
     * Legacy `!isset($x) || empty($x)` check.
     */
    protected function requireFilled(Request $request, string $field, ?string $message = null): mixed
    {
        if (! $request->filled($field)) {
            throw new ApiException($message ?? "$field is required");
        }

        return $request->input($field);
    }

    /**
     * Legacy endpoints inserted/updated the raw $_REQUEST. Keep that, but only for
     * real columns of the table (and never the primary key).
     */
    protected function onlyColumns(string $table, array $input, array $except = ['id']): array
    {
        $columns = array_diff(Schema::getColumnListing($table), $except);

        return array_intersect_key($input, array_flip($columns));
    }

    protected function now(): string
    {
        return Carbon::now(self::TZ)->format('Y-m-d H:i:s');
    }

    protected function uploadsUrl(string $path, ?string $file): ?string
    {
        return $file === null ? null : url('uploads/'.$path.'/'.$file);
    }

    protected function rows($collection): array
    {
        return collect($collection)->map(fn ($row) => (array) $row)->all();
    }
}
