<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Academics\CalendarFeeds;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public iCalendar endpoint a calendar app subscribes to (Action Plan
 * 1.10). The secret token is the only key; no session or school header.
 */
class CalendarFeedController extends Controller
{
    public function __invoke(string $token): Response
    {
        $calendar = app(CalendarFeeds::class)->render($token);
        abort_if($calendar === null, 404);

        return response($calendar, 200, ['Content-Type' => 'text/calendar; charset=utf-8', 'Content-Disposition' => 'inline; filename="navigateed.ics"', 'X-Robots-Tag' => 'noindex']);
    }
}
