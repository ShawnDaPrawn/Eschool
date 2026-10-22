<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The API's response envelope, {error, message, data, code}.
 *
 * The response methods build and return the response; the caller returns it:
 * `return ResponseService::successResponse(...)`. They used to send it and
 * exit(), which skipped the response middleware and made in-process tests
 * impossible. Code that can't return (a helper whose callers expect it to stop
 * the request) throws it instead: `throw new HttpResponseException(...)`.
 * Laravel then sends the thrown response, and errorResponse() passes it through
 * when a catch block hands it the exception. ControllerMethodCallsTest fails on
 * a call whose response is neither returned nor thrown.
 */
class ResponseService
{
    /** Longest single value, and longest set of request data, written to the log. */
    private const LOG_VALUE_LIMIT = 1000;

    private const LOG_CONTEXT_LIMIT = 10000;

    /**
     * @return Application|RedirectResponse|Redirector|true
     */
    public static function noPermissionThenRedirect($permission)
    {
        if (! Auth::user()->can($permission)) {
            // Throw so the controller stops here. send() alone let it carry on
            // and output the protected page after the redirect.
            throw new HttpResponseException(redirect(route('home'))->withErrors([
                'message' => trans('no_permission_message'),
            ]));
        }

        return true;
    }

    /**
     * Stops the request with a JSON error (by throwing it) unless the user has the permission.
     *
     * @return true
     */
    public static function noPermissionThenSendJson($permission)
    {
        if (! Auth::user()->can($permission)) {
            throw new HttpResponseException(self::errorResponse('no_permission_message'));
        }

        return true;
    }

    /**
     * @return Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector|true
     */
    // Check user role
    public static function noRoleThenRedirect($role)
    {
        if (! Auth::user()->hasRole($role)) {
            throw new HttpResponseException(redirect(route('home'))->withErrors([
                'message' => trans('no_permission_message'),
            ]));
        }

        return true;
    }

    /**
     * @return bool|Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     */
    public static function noAnyRoleThenRedirect(array $role)
    {
        if (! Auth::user()->hasAnyRole($role)) {
            throw new HttpResponseException(redirect(route('home'))->withErrors([
                'message' => trans('no_permission_message'),
            ]));
        }

        return true;
    }

    //    /**
    //     * @param $role
    //     * @return true
    //     */
    //    public static function noRoleThenSendJson($role)
    //    {
    //        if (!Auth::user()->hasRole($role)) {
    //            self::errorResponse("You Don't have enough permissions");
    //        }
    //        return true;
    //    }

    /**
     * If User don't have any of the permission that is specified in Array then Redirect will happen
     *
     * @return RedirectResponse|true
     */
    public static function noAnyPermissionThenRedirect(array $permissions)
    {
        if (! Auth::user()->canany($permissions)) {
            throw new HttpResponseException(redirect()->back()->withErrors([
                'message' => trans('no_permission_message'),
            ]));
        }

        return true;
    }

    /**
     * Stops the request with a JSON error (by throwing it) unless the user has one of the permissions.
     *
     * @return true
     */
    public static function noAnyPermissionThenSendJson(array $permissions)
    {
        if (! Auth::user()->canany($permissions)) {
            throw new HttpResponseException(self::errorResponse("You Don't have enough permissions"));
        }

        return true;
    }

    public static function successResponse(string $message = 'Success', $data = null, array $customData = [], $code = null): JsonResponse
    {
        return response()->json(array_merge([
            'error' => false,
            'message' => trans($message),
            'data' => $data,
            'code' => $code ?? 200,
        ], $customData));
    }

    /**
     * @return Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     */
    public static function successRedirectResponse(string $message = 'success', $url = null)
    {
        return isset($url) ? redirect($url)->with([
            'success' => trans($message),
        ]) : redirect()->back()->with([
            'success' => trans($message),
        ]);
    }

    /**
     * The error envelope. With an exception, it is logged first; a response
     * thrown with HttpResponseException (by a helper that stops the request,
     * such as resolveChild()) is returned as it is and not logged, so a catch
     * block that hands its exception here keeps the helper's answer.
     *
     * @param  string  $message  - Pass the Translatable Field
     * @param  null  $data
     * @param  null  $code
     * @param  null  $e
     */
    public static function errorResponse(string $message = 'Error Occurred', $data = null, $code = null, $e = null): Response
    {
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        if ($e) {
            self::logErrorResponse($e);
            self::logCurlRequest();
        }

        return response()->json([
            'error' => true,
            'message' => trans($message),
            'data' => $data,
            'code' => $code ?? config('constants.RESPONSE_CODE.EXCEPTION_ERROR'),
            // Exception text, file paths and line numbers are only shown while debugging; they're always in the log.
            'details' => (config('app.debug') && ! empty($e) && is_object($e)) ? $e->getMessage().' --> '.$e->getFile().' At Line : '.$e->getLine() : '',
        ]);
    }

    /**
     * @return Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     */
    public static function errorRedirectResponse($url = null, string $message = 'Error Occurred')
    {
        return (($url != null) ? redirect($url) : redirect()->back())->withErrors([
            'message' => trans($message),
        ]);
    }

    /**
     * @param  null  $data
     * @param  null  $code
     */
    public static function warningResponse(string $message = 'Error Occurred', $data = null, $code = null): JsonResponse
    {
        return response()->json([
            'error' => false,
            'warning' => true,
            'code' => $code,
            'message' => trans($message),
            'data' => $data,
        ]);
    }

    /**
     * @param  null  $data
     */
    public static function validationError(string $message = 'Error Occurred', $data = null): Response
    {
        return self::errorResponse($message, $data, config('constants.RESPONSE_CODE.VALIDATION_ERROR'));
    }

    /**
     * @param  string  $logMessage
     * @param  string  $responseMessage
     * @param  bool  $jsonResponse
     * @return void
     */
    public static function logErrorResponse(Throwable|Exception $e)
    {
        report($e);

        // The path without its query string, and only the names of the fields:
        // logs carry no personal data beyond ids.
        Log::error(self::truncate($e->getMessage(), self::LOG_CONTEXT_LIMIT).'---> '.$e->getFile().' At Line : '.$e->getLine()."\n\n".request()->method().' : /'.ltrim(self::truncate(request()->path()), '/')."\nParams : ", self::requestShape());
    }

    /**
     * Data as it may go into the log: secrets redacted and long values cut
     * short, so one large request can't write a huge log line.
     */
    public static function loggable(array $values): array
    {
        $values = self::redact($values);
        $json = json_encode($values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($json === false || strlen($json) > self::LOG_CONTEXT_LIMIT) {
            return ['truncated' => self::truncate((string) $json, self::LOG_CONTEXT_LIMIT)];
        }

        return $values;
    }

    /**
     * Keys whose values never go into the logs (passwords, tokens, keys, secrets).
     */
    private static function isSensitiveKey(string|int $key): bool
    {
        return (bool) preg_match('/pass|token|secret|key|signature|authorization|cookie|otp/i', (string) $key);
    }

    private static function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (self::isSensitiveKey($key)) {
                $values[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $values[$key] = self::redact($value);
            } elseif (is_string($value)) {
                $values[$key] = self::truncate($value);
            }
        }

        return $values;
    }

    private static function truncate(string $value, int $limit = self::LOG_VALUE_LIMIT): string
    {
        return strlen($value) > $limit ? mb_strcut($value, 0, $limit).'... ['.strlen($value).' bytes]' : $value;
    }

    /**
     * Which request led to an error, without personal data (logs carry ids
     * only): the method, the path, the names but never the values of the
     * query, form and file fields, and the signed-in user's id. It used to
     * write the request as a curl command with every field's value.
     */
    public static function logCurlRequest(): void
    {
        $request = request();

        Log::error('Request: '.strtoupper($request->method()).' /'.ltrim(self::truncate($request->path()), '/'), self::requestShape());
    }

    /**
     * @return array{query: list<string>, fields: list<string>, files: list<string>, user_id: int|string|null}
     */
    private static function requestShape(): array
    {
        $request = request();

        return [
            'query' => self::fieldNames($request->query()),
            'fields' => self::fieldNames(array_diff_key($request->input(), $request->query())),
            'files' => self::fieldNames($request->files->all()),
            'user_id' => $request->user()?->getAuthIdentifier(),
        ];
    }

    /**
     * Dotted field names at any depth, list positions shown as *; at most 100.
     *
     * @return list<string>
     */
    private static function fieldNames(array $values, string $prefix = ''): array
    {
        $names = [];
        foreach ($values as $key => $value) {
            $name = $prefix.(is_int($key) ? '*' : (string) $key);
            $names = array_merge($names, is_array($value) && $value !== [] ? self::fieldNames($value, $name.'.') : [$name]);
        }

        return array_slice(array_values(array_unique($names)), 0, 100);
    }
}
