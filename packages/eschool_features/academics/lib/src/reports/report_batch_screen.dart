import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'report_comment_cubit.dart';
import 'report_comment_screen.dart';
import 'report_family_cubit.dart';
import 'report_pdf_screen.dart';
import 'report_repository.dart';
import 'report_results.dart';

class ReportBatchScreen extends StatefulWidget {
  const ReportBatchScreen({
    required this.repository,
    required this.batchId,
    this.banks = const [],
    super.key,
  });
  final ReportRepository repository;
  final int batchId;
  final List<AcademicReportCommentBank> banks;
  @override
  State<ReportBatchScreen> createState() => _ReportBatchScreenState();
}

class _ReportBatchScreenState extends State<ReportBatchScreen> {
  AcademicReportDetailData? _detail;
  bool _busy = false;
  String? _error, _notice;
  final _keys = ReportOperationKeys();
  Timer? _poll;
  int _polls = 0;
  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _poll?.cancel();
    super.dispose();
  }

  Future<void> _load({bool quiet = false}) async {
    if (_busy) return;
    _poll?.cancel();
    setState(() {
      _busy = true;
      if (!quiet) _error = null;
    });
    try {
      final detail = await widget.repository.batch(widget.batchId);
      if (!mounted) return;
      setState(() => _detail = detail);
      if (detail.state == 'approved' && _polls++ < 60) {
        _poll = Timer(const Duration(seconds: 5), () => _load(quiet: true));
      }
    } catch (error) {
      if (mounted) {
        setState(() {
          _detail = null;
          _error = reportFailure(error);
        });
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<bool> _confirm(
    String title,
    Widget content, {
    String action = 'Continue',
  }) async =>
      await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(title),
          content: SizedBox(
            width: 640,
            child: SingleChildScrollView(child: content),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Keep reviewing'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(action),
            ),
          ],
        ),
      ) ??
      false;
  Future<void> _action(String action) async {
    final detail = _detail;
    if (detail == null || _busy) return;
    _poll?.cancel();
    setState(() {
      _busy = true;
      _error = null;
      _notice = null;
    });
    var fingerprint = detail.fingerprint;
    try {
      var proceed = true;
      if (action == 'refresh') {
        final source = await widget.repository.source(detail.id);
        if (!mounted) return;
        fingerprint = source.fingerprint;
        proceed =
            source.canPrepare &&
            await _confirm(
              'Replace draft evidence?',
              Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Review the current evidence. Refresh clears all signatures and requires new approval.',
                  ),
                  for (final payload in source.cards)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      child: ReportResults(payload: payload),
                    ),
                ],
              ),
              action: 'Refresh reviewed evidence',
            );
        if (!source.canPrepare) {
          throw ApiException(
            'Current evidence is incomplete. Review the selected learners and plans.',
            isApiMessage: true,
          );
        }
      } else if (action == 'release') {
        final preview = await widget.repository.releasePreview(detail.id);
        if (!mounted) return;
        if (!preview.canRelease) {
          throw ApiException(
            'The report PDFs are not ready for release. Refresh the batch and review each PDF.',
            isApiMessage: true,
          );
        }
        fingerprint = preview.fingerprint;
        if (preview.revision != detail.revision ||
            fingerprint != detail.fingerprint) {
          throw ApiException(
            'The batch changed. Refresh and review it before release.',
            isApiMessage: true,
          );
        }
        proceed = await _confirm(
          'Release these report cards?',
          Text(
            '${preview.cards.length} reviewed reports will become available to their learners and current guardians. One action queues all eligible family notifications. Fees never restrict delivery.',
          ),
          action: 'Release and notify families',
        );
      } else if (action.endsWith('sign')) {
        proceed = await _confirm(
          action == 'grade-sign'
              ? 'Grade-head sign-off?'
              : 'Principal sign-off?',
          Text(
            'You are signing the displayed evidence and comments for ${detail.cards.length} learners. Any later change requires fresh approval.',
          ),
          action: 'Sign reviewed reports',
        );
      }
      if (!proceed || !mounted) return;
      final body = AcademicReportActionWrite(
        revision: detail.revision,
        fingerprint: fingerprint,
      );
      await widget.repository.action(
        detail.id,
        action,
        body,
        _keys.key('$action:${detail.id}', body.toJson()),
      );
      _keys.completed('$action:${detail.id}', body.toJson());
      if (mounted) {
        _detail = await widget.repository.batch(detail.id);
        if (mounted) {
          setState(
            () => _notice = action == 'release'
                ? 'Reports released. Family notifications are queued.'
                : 'Report batch updated.',
          );
        }
      }
    } catch (error) {
      if (mounted) setState(() => _error = reportFailure(error));
    } finally {
      if (mounted) {
        setState(() => _busy = false);
        if (_detail?.state == 'approved') {
          _poll = Timer(const Duration(seconds: 5), () => _load(quiet: true));
        }
      }
    }
  }

  Future<void> _comment(
    AcademicReportDetailCard card,
    int? subject,
    String label,
  ) async {
    final detail = _detail!;
    final key = subject == null ? 'homeroom' : 'subject:$subject';
    final old = card.comments.where((c) => c.scopeKey == key).firstOrNull;
    final row = AcademicReportCommentRow(
      cardId: card.id,
      batchId: detail.id,
      batchRevision: detail.revision,
      learnerId: card.learnerId,
      learnerName: card.payload.learnerName,
      periodName:
          '${card.payload.yearName} · ${card.payload.termName ?? 'Annual report'}',
      subjectId: subject,
      scopeLabel: label,
      revision: old?.revision ?? 0,
      text: old?.text ?? '',
    );
    _poll?.cancel();
    await Navigator.of(context).push(
      AdaptivePageRoute(
        builder: (_) => ReportCommentScreen(
          repository: widget.repository,
          row: row,
          year: detail.selection.sessionYearId,
          banks: widget.banks,
          administrativeCorrection: true,
        ),
      ),
    );
    if (mounted) _load();
  }

  @override
  Widget build(BuildContext context) {
    final detail = _detail;
    return ScreenScaffold(
      title: 'Report batch ${widget.batchId}',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_busy) const LinearProgressIndicator(),
          if (_error != null)
            Text(
              _error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          if (_notice != null) Text(_notice!),
          TextButton.icon(
            onPressed: _busy ? null : _load,
            icon: const Icon(Icons.refresh),
            label: const Text('Refresh batch'),
          ),
          if (detail != null) ...[
            Text(
              reportStatus(detail.state),
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            Text('${detail.design.name} · ${detail.cards.length} learners'),
            if (detail.signatures.gradeHead != null)
              Text('Grade head: ${detail.signatures.gradeHead!.name}'),
            if (detail.signatures.principal != null)
              Text('Principal: ${detail.signatures.principal!.name}'),
            if (detail.state == 'approved')
              const Text(
                'Private PDFs are being generated. This page refreshes while the jobs run.',
              ),
            Wrap(
              spacing: 12,
              runSpacing: 8,
              children: [
                if (detail.state != 'released')
                  OutlinedButton(
                    onPressed: _busy ? null : () => _action('refresh'),
                    child: const Text('Review current evidence'),
                  ),
                if (detail.state == 'draft' && detail.canGradeSign)
                  FilledButton(
                    onPressed: _busy ? null : () => _action('grade-sign'),
                    child: const Text('Grade-head sign-off'),
                  ),
                if (detail.state == 'grade_signed' && detail.canPrincipalSign)
                  FilledButton(
                    onPressed: _busy ? null : () => _action('principal-sign'),
                    child: const Text('Principal sign-off'),
                  ),
                if (['approved', 'ready'].contains(detail.state))
                  OutlinedButton(
                    onPressed: _busy ? null : () => _action('retry'),
                    child: const Text('Retry PDF generation'),
                  ),
                if (detail.state == 'ready')
                  FilledButton(
                    onPressed: _busy ? null : () => _action('release'),
                    child: const Text('Preview release'),
                  ),
              ],
            ),
            for (final card in detail.cards)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      ReportResults(payload: card.payload),
                      for (final comment in card.comments)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          child: Text(
                            '${comment.scopeKey == 'homeroom' ? 'Homeroom' : card.payload.subjects.where((s) => 'subject:${s.subjectId}' == comment.scopeKey).firstOrNull?.subjectName ?? 'Subject'}: ${comment.text}',
                          ),
                        ),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          if (card.pdfReady)
                            OutlinedButton.icon(
                              onPressed: _busy
                                  ? null
                                  : () => Navigator.of(context).push(
                                      AdaptivePageRoute(
                                        builder: (_) => ReportPdfScreen(
                                          repository: widget.repository,
                                          card: card.id,
                                          batch: detail.id,
                                          title:
                                              '${card.payload.learnerName} · Report preview',
                                        ),
                                      ),
                                    ),
                              icon: const Icon(Icons.picture_as_pdf_outlined),
                              label: const Text('Preview PDF'),
                            ),
                          if (detail.state != 'released') ...[
                            TextButton(
                              onPressed: _busy
                                  ? null
                                  : () => _comment(card, null, 'Homeroom'),
                              child: const Text('Homeroom comment'),
                            ),
                            for (final subject in card.payload.subjects)
                              TextButton(
                                onPressed: _busy
                                    ? null
                                    : () => _comment(
                                        card,
                                        subject.subjectId,
                                        subject.subjectName,
                                      ),
                                child: Text('${subject.subjectName} comment'),
                              ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ],
      ),
    );
  }
}
