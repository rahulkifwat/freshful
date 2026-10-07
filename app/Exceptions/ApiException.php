<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown from API controllers for expected failures (missing params, not found...).
 * Rendered in bootstrap/app.php as the legacy shape: {"result": false, "message": "..."}.
 */
class ApiException extends Exception
{
}
