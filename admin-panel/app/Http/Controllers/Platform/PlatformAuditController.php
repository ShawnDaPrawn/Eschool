<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\Saas\PlatformAuditLog;
use App\Services\Platform\Saas\PlatformStaffAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * /saas/audit: the platform audit log (SA.3), filtered by school, action,
 * staff member and date, and exported as CSV. Platform staff only (the
 * platform.staff middleware, checked again here from fresh rows).
 * See engineering/specs/platform/hq-audit-log.md.
 */
class PlatformAuditController extends Controller
{
    public function __construct(private readonly PlatformAuditLog $log, private readonly PlatformStaffAccess $access) {}

    public function index(Request $request): View
    {
        $this->access->actor($request->user());
        $filters = PlatformAuditLog::filters($request->query());

        return view('saas.audit', [
            'filters' => $filters,
            'rows' => $this->log->search($filters, max(1, $request->integer('page', 1)))->withQueryString(),
            'choices' => $this->log->choices(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $actor = $this->access->actor($request->user());
        $rows = $this->log->export(PlatformAuditLog::filters($request->query()), $actor->id);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['when', 'school', 'who', 'email', 'action', 'label', 'subject', 'changes', 'reason', 'ip', 'request_id'], ',', '"', '');
            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), [
                    $row['at']->format('Y-m-d H:i:s'), $row['school'], $row['actor'], $row['actor_email'], $row['action'], $row['label'],
                    $row['subject'], implode('; ', $row['changes']), $row['reason'], $row['ip'], $row['request_id'],
                ]), ',', '"', '');
            }
            fclose($out);
        }, 'hq-audit-'.now(PlatformAuditLog::TIMEZONE)->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** A cell a spreadsheet will not run as a formula. */
    private static function cell(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
