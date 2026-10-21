<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Services\ParentPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Parent app: NavigateEd's terms, then the school's privacy notice and terms.
 */
class PolicyController extends Controller
{
    public function __construct(private readonly ParentPolicyService $policies) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'error' => false,
            'message' => 'Success',
            'data' => $this->policies->status($request->user()),
            'code' => 200,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $result = $this->policies->accept(
            $request->user(),
            (string) $request->input('document', ''),
            (string) $request->input('version', ''),
        );

        return response()->json([
            'error' => ! $result['ok'],
            'message' => $result['message'],
            'data' => $result['status'],
            'code' => $result['ok'] ? 200 : null,
        ]);
    }
}
