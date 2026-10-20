<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckChild
{
    /**
     * Only lets a parent through for one of their own children. The child is
     * stored on the request as the 'child' attribute for the controller.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $parent = $request->user()?->parent;
        $childId = filter_var($request->child_id, FILTER_VALIDATE_INT);

        $child = ($parent && $childId) ? $parent->children()->whereKey($childId)->first() : null;
        if (empty($child)) {
            return response()->json(array(
                'error' => true,
                'message' => "Invalid Child ID Passed.",
                'code'=> 105,
            ));
        }
        $request->attributes->set('child', $child);

        return $next($request);
    }
}
