<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\Notifications\EmailUnsubscribe;
use Illuminate\Contracts\View\View;

/**
 * The link in an optional notice email (Action Plan 2.4). GET shows what it
 * would switch off and asks to confirm; POST switches it off. Mail providers
 * POST to the same signed link for one-click unsubscribe (RFC 8058), so the
 * POST needs no session or CSRF token: the signature is the authority.
 */
class EmailUnsubscribeController extends Controller
{
    public function __construct(private readonly EmailUnsubscribe $unsubscribe) {}

    public function show(int $user, int $school, string $type): View
    {
        return view('emails.unsubscribe', ['preview' => $this->unsubscribe->preview($user, $school, $type), 'result' => null]);
    }

    public function store(int $user, int $school, string $type): View
    {
        $result = $this->unsubscribe->apply($user, $school, $type);

        return view('emails.unsubscribe', ['preview' => $this->unsubscribe->preview($user, $school, $type), 'result' => $result]);
    }
}
