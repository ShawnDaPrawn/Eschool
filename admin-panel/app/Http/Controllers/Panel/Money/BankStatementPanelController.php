<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\BankStatementPanelRequest;
use App\Models\Money\BankStatementImport;
use App\Models\Money\BankStatementLine;
use App\Models\Money\BankStatementResolution;
use App\Models\Money\FeeAccount;
use App\Services\Money\BankCsv;
use App\Services\Money\BankStatements;
use App\Services\Money\FeePayments;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BankStatementPanelController extends Controller
{
    public function index(BankStatementPanelRequest $request): Response
    {
        $status = $request->input('status', 'unmatched');
        $lines = BankStatementLine::leftJoin('bank_statement_resolutions as r', function ($j) {
            $j->on('r.school_id', '=', 'bank_statement_lines.school_id')->on('r.bank_statement_line_id', '=', 'bank_statement_lines.id');
        })->when($status === 'unmatched', fn ($q) => $q->whereNull('r.id'))
            ->when(in_array($status, ['matched', 'excluded'], true), fn ($q) => $q->where('r.kind', $status))
            ->orderByDesc('bank_statement_lines.received_on')->orderByDesc('bank_statement_lines.id')->paginate(30, ['bank_statement_lines.*', 'r.kind as resolution_kind'])->withQueryString();
        $imports = BankStatementImport::whereNotNull('sealed_at')->orderByDesc('id')->limit(10)->get();
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.bank.index', compact('lines', 'imports', 'status', 'canWrite'));
    }

    public function form(BankStatementPanelRequest $request): Response
    {
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.bank.form', compact('canWrite'));
    }

    public function template(BankStatementPanelRequest $request): Response
    {
        return response(implode(',', BankCsv::HEADER)."\n", 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="bank-import-template.csv"']);
    }

    public function upload(BankStatementPanelRequest $request): Response
    {
        $file = $request->file('statement');
        abort_unless($file instanceof UploadedFile, 422);
        $preview = app(BankStatements::class)->previewImport($request->user(), $file->getContent());

        return response()->view('money.bank.import-review', compact('preview'));
    }

    public function import(BankStatementPanelRequest $request): Response
    {
        app(BankStatements::class)->import($request->user(), $request->validated());

        return redirect()->route('bank-statements.index', [], 303)->with('success', __('Bank rows imported once. No money was posted.'));
    }

    public function show(BankStatementPanelRequest $request): Response
    {
        $line = BankStatementLine::findOrFail((int) $request->route('line'));
        $resolution = BankStatementResolution::where('bank_statement_line_id', $line->id)->first();
        $suggestion = app(BankStatements::class)->suggestion($line);
        $accounts = FeeAccount::orderBy('name')->get(['id', 'name']);
        $account = $request->filled('account_id') ? FeeAccount::findOrFail($request->integer('account_id')) : $suggestion;
        $holders = $account === null ? [] : app(FeePayments::class)->holders($account);
        $learnerOptions = [];
        $learnerIds = [];
        if ($account !== null) {
            foreach (array_keys($holders) as $holder) {
                $learnerIds = [...$learnerIds, ...app(FeePayments::class)->eligibleLearners($account, (int) $holder)];
            }
            $users = DB::table('students as s')->join('users as u', 'u.id', '=', 's.user_id')->where('s.school_id', app(CurrentSchool::class)->id())
                ->whereIn('s.id', array_unique($learnerIds))->get(['s.id', 'u.first_name', 'u.last_name']);
            foreach ($users as $user) {
                $learnerOptions[(int) $user->id] = trim($user->first_name.' '.$user->last_name);
            }
        }
        $canWrite = $resolution === null && app(ModuleGate::class)->writable('fees');

        return response()->view('money.bank.show', compact('line', 'resolution', 'suggestion', 'accounts', 'account', 'holders', 'learnerOptions', 'canWrite'));
    }

    public function review(BankStatementPanelRequest $request): Response
    {
        $line = BankStatementLine::findOrFail((int) $request->route('line'));
        $preview = app(BankStatements::class)->previewMatch($request->user(), (int) $line->id, $request->validated());

        return response()->view('money.bank.match-review', compact('line', 'preview'));
    }

    public function resolve(BankStatementPanelRequest $request): Response
    {
        app(BankStatements::class)->resolve($request->user(), (int) $request->route('line'), $request->validated());

        return redirect()->route('bank-statements.show', ['line' => $request->route('line')], 303)->with('success', __('Bank transaction resolved once.'));
    }
}
