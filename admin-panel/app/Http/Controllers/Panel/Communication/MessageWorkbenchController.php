<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\MessagePanelRequest;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Content\ContentMarkdown;
use App\Services\Communication\CommunicationAudiences;
use App\Services\Communication\CommunicationOperations;
use App\Services\Communication\CommunicationRecipients;
use App\Services\Communication\CommunicationRules;
use App\Services\Communication\MessageDraftRestoration;
use App\Services\Communication\MessageFlow;
use App\Services\Communication\MessageQueries;
use App\Services\Communication\MessageReport;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class MessageWorkbenchController extends Controller
{
    public function index(MessagePanelRequest $r): Response
    {
        $queries = app(MessageQueries::class);
        $message = $r->validated('message_id') === null ? null : $queries->editor($r->user(), (int) $r->validated('message_id'), true);
        $config = $queries->configuration($r->user(), $message['audience']['session_year_id'] ?? ($r->validated('session_year_id') === null ? null : (int) $r->validated('session_year_id')), true);
        $messages = $queries->drafts($r->user(), (int) $r->validated('page', 1), true);
        $contacts = $queries->contacts($r->user(), $config['session_year_id'], (int) $r->validated('contact_page', 1));
        $audience = $r->validated('audience_id') === null ? null : app(CommunicationAudiences::class)->owned($r->user(), (int) $r->validated('audience_id'));
        $restored = app(MessageDraftRestoration::class)->read($r, $message['id'] ?? null);
        $fixed = $audience !== null && ! $audience->isSmart() ? $audience : null;
        $selectedContacts = $fixed === null ? [] : array_values(array_intersect($fixed->definition['user_ids'], array_keys(app(CommunicationRecipients::class)->allowed($r->user(), $config['session_year_id'], app(AcademicAuthority::class)->today()))));
        $removedContacts = $fixed === null ? 0 : count($fixed->definition['user_ids']) - count($selectedContacts);
        $smart = $audience !== null && $audience->isSmart() ? $audience : null;
        $smartCount = $smart === null ? null : app(CommunicationAudiences::class)->count($smart);
        $preview = $restored === null && $r->boolean('preview_message') && $message !== null ? app(MessageFlow::class)->preview($r->user(), $message['id']) : null;

        return response()->view('communication.workbench', ['configuration' => $config, 'messages' => $messages, 'message' => $message,
            'contacts' => $contacts, 'audience' => $audience, 'preview' => $preview, 'restored' => $restored, 'selectedContacts' => $selectedContacts, 'removedContacts' => $removedContacts, 'smart' => $smart, 'smartCount' => $smartCount,
            'rendered' => $message === null ? null : app(ContentMarkdown::class)->html($message['markdown']),
            'roleRules' => $config['can_manage'] ? app(CommunicationRules::class)->listing($r->user()) : []]);
    }

    /** The sender's delivery report for a published message (Action Plan 2.4). */
    public function report(MessagePanelRequest $r, int $message): Response
    {
        $report = app(MessageReport::class)->for($r->user(), $message, (int) $r->validated('page', 1));

        return response()->view('communication.report', ['report' => $report, 'timezone' => app(MessageQueries::class)->configuration($r->user(), null, true)['timezone']]);
    }

    private function done(array $receipt, string $text): Response
    {
        return redirect()->route('communication-centre.index', ['message_id' => $receipt['id']])->with('success', __($text));
    }

    public function create(MessagePanelRequest $r): Response
    {
        try {
            return $this->done(app(MessageFlow::class)->save($r->user(), null, $r->validated()), 'Message draft saved');
        } catch (ValidationException $error) {
            return app(MessageDraftRestoration::class)->retain($r, null, $error->errors());
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 422) {
                throw $error;
            }

            return app(MessageDraftRestoration::class)->retain($r, null, ['message' => $error->getMessage()]);
        }
    }

    public function save(MessagePanelRequest $r, int $message): Response
    {
        try {
            return $this->done(app(MessageFlow::class)->save($r->user(), $message, $r->validated()), 'Message draft saved');
        } catch (ValidationException $error) {
            return app(MessageDraftRestoration::class)->retain($r, $message, $error->errors());
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() === 422) {
                return app(MessageDraftRestoration::class)->retain($r, $message, ['message' => $error->getMessage()]);
            }
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return response()->view('communication.conflict', ['submitted' => $r->validated(), 'current' => app(MessageQueries::class)->editor($r->user(), $message, true)], 409);
        }
    }

    public function submit(MessagePanelRequest $r, int $message): Response
    {
        return $this->done(app(MessageFlow::class)->submit($r->user(), $message, $r->validated()), 'Message submitted. Its approval and delivery state appears here.');
    }

    public function decision(MessagePanelRequest $r, int $message): Response
    {
        return $this->done(app(MessageFlow::class)->decide($r->user(), $message, $r->validated()), 'Message decision saved');
    }

    public function withdraw(MessagePanelRequest $r, int $message): Response
    {
        return $this->done(app(MessageFlow::class)->withdraw($r->user(), $message, $r->validated()), 'Message withdrawn');
    }

    public function audienceCreate(MessagePanelRequest $r): Response
    {
        $receipt = app(CommunicationAudiences::class)->save($r->user(), null, $r->validated());

        return redirect()->route('communication-centre.index', ['audience_id' => $receipt['id'], 'session_year_id' => $r->validated('session_year_id')])->with('success', __('Audience saved'));
    }

    public function audienceSave(MessagePanelRequest $r, int $audience): Response
    {
        $receipt = app(CommunicationAudiences::class)->save($r->user(), $audience, $r->validated());

        return redirect()->route('communication-centre.index', ['audience_id' => $receipt['id'], 'session_year_id' => $r->validated('session_year_id')])->with('success', __('Audience saved'));
    }

    public function ruleSave(MessagePanelRequest $r, int $role): Response
    {
        app(CommunicationOperations::class)->once($r->user(), 'rule', ['role' => $role] + $r->validated(), fn () => abort_unless(app(CommunicationRules::class)->manages($r->user()), 403), fn () => app(CommunicationRules::class)->save($r->user(), $role, $r->validated()));

        return redirect()->route('communication-centre.index')->with('success', __('Message author rule saved'));
    }
}
