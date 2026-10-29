<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Stable v1 error envelope for installed clients, without an obsolete writer. */
final class RetiredFeeApiController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $response = ResponseService::errorResponse('Update the app to use current fee statements and receipts. Online payments are not available yet.', null, 410);
        $response->headers->set('Cache-Control', 'no-store, private');
        if (str_ends_with($request->path(), '-pdf')) {
            $response->setStatusCode(410);
        }

        return $response;
    }
}
