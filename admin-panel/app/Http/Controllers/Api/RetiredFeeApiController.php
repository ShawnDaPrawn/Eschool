<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Stable v1 error envelope for installed clients, without an obsolete writer. */
final class RetiredFeeApiController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return response()->json([
            'error' => true,
            'message' => __('Update the app to use current fee statements and receipts. Online payments are not available yet.'),
            'data' => null,
            'code' => 410,
            'details' => '',
        ], str_ends_with($request->path(), '-pdf') ? 410 : 200, ['Cache-Control' => 'no-store, private']);
    }
}
