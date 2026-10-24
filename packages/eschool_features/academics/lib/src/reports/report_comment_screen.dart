import '../localization/academic_text.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'report_comment_cubit.dart';
import 'report_family_cubit.dart';
import 'report_repository.dart';

class ReportCommentScreen extends StatefulWidget {
  const ReportCommentScreen({
    required this.repository,
    required this.row,
    required this.year,
    this.banks = const [],
    this.administrativeCorrection = false,
    super.key,
  });
  final ReportRepository repository;
  final AcademicReportCommentRow row;
  final int year;
  final List<AcademicReportCommentBank> banks;
  final bool administrativeCorrection;
  @override
  State<ReportCommentScreen> createState() => _ReportCommentScreenState();
}

class _ReportCommentScreenState extends State<ReportCommentScreen> {
  late final ReportCommentCubit _cubit = ReportCommentCubit(
    widget.repository,
    widget.row,
  );
  late final TextEditingController _text = TextEditingController(
    text: widget.row.text,
  );
  final _reason = TextEditingController();
  bool _reviewing = false, _discarding = false;
  @override
  void dispose() {
    _cubit.close();
    _text.dispose();
    _reason.dispose();
    super.dispose();
  }

  Future<bool> _leave() async {
    if (!_cubit.state.dirty) return true;
    return await showDialog<bool>(
          context: context,
          builder: (context) => AlertDialog(
            title: const AcademicText('Keep editing this comment?'),
            content: const AcademicText(
              'Your unsaved comment is kept on this screen. Leaving discards it.',
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const AcademicText('Keep editing'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context, true),
                child: const AcademicText('Discard and leave'),
              ),
            ],
          ),
        ) ??
        false;
  }

  Future<void> _compare() async {
    setState(() => _reviewing = true);
    try {
      AcademicReportCommentRow? latest;
      if (widget.administrativeCorrection) {
        final detail = await widget.repository.batch(widget.row.batchId);
        final card = detail.cards
            .where((c) => c.id == widget.row.cardId)
            .firstOrNull;
        if (card == null) {
          throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
        }
        final key = widget.row.subjectId == null
            ? 'homeroom'
            : 'subject:${widget.row.subjectId}';
        final comment = card.comments
            .where((c) => c.scopeKey == key)
            .firstOrNull;
        latest = AcademicReportCommentRow(
          cardId: card.id,
          batchId: detail.id,
          batchRevision: detail.revision,
          learnerId: card.learnerId,
          learnerName: card.payload.learnerName,
          periodName: widget.row.periodName,
          subjectId: widget.row.subjectId,
          scopeLabel: widget.row.scopeLabel,
          revision: comment?.revision ?? 0,
          text: comment?.text ?? '',
        );
      } else {
        var page = 1;
        while (latest == null) {
          final data = await widget.repository.comments(
            widget.year,
            page: page,
          );
          latest = data.rows
              .where(
                (r) =>
                    r.cardId == widget.row.cardId &&
                    r.subjectId == widget.row.subjectId,
              )
              .firstOrNull;
          if (page >= data.lastPage) break;
          page++;
        }
      }
      if (!mounted) return;
      if (latest == null) {
        throw ApiException(
          'This comment is no longer available. Return to reports and check your access.',
          isApiMessage: true,
        );
      }
      final current = latest;
      final keep = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: const AcademicText('Compare comments'),
          content: SizedBox(
            width: 600,
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  const AcademicText('Latest saved'),
                  SelectableText(
                    current.text.isEmpty ? 'No comment' : current.text,
                  ),
                  const SizedBox(height: 16),
                  const AcademicText('Your draft'),
                  SelectableText(_cubit.state.text),
                ],
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const AcademicText('Keep reviewing'),
            ),
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const AcademicText('Use saved'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const AcademicText('Keep my draft'),
            ),
          ],
        ),
      );
      if (keep != null && mounted) {
        _cubit.reviewed(current, keepDraft: keep);
        _text.text = _cubit.state.text;
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(reportFailure(error))));
      }
    } finally {
      if (mounted) setState(() => _reviewing = false);
    }
  }

  @override
  Widget build(
    BuildContext context,
  ) => BlocBuilder<ReportCommentCubit, ReportCommentState>(
    bloc: _cubit,
    builder: (context, state) => PopScope(
      canPop: _discarding || (!state.dirty && !state.busy),
      onPopInvokedWithResult: (didPop, result) async {
        if (!didPop && !state.busy && await _leave() && mounted) {
          setState(() => _discarding = true);
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (mounted) Navigator.pop(context);
          });
        }
      },
      child: ScreenScaffold(
        title: '${widget.row.learnerName} · ${widget.row.scopeLabel}',
        body: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text(
              widget.row.periodName,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 12),
              child: AcademicText(
                'Saving a changed comment clears previous signatures. Review the report again before release.',
              ),
            ),
            if (state.error != null)
              Text(
                state.error!,
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final bank in widget.banks.where(
                  (b) => b.active && b.subjectId == widget.row.subjectId,
                ))
                  ActionChip(
                    label: Text(bank.label),
                    onPressed: state.busy
                        ? null
                        : () {
                            _text.text = bank.text;
                            _cubit.change(bank.text);
                          },
                  ),
              ],
            ),
            TextField(
              controller: _text,
              enabled: !state.busy,
              maxLength: 3000,
              minLines: 5,
              maxLines: 12,
              decoration: InputDecoration(
                labelText: academicText(context, 'Report comment'),
                alignLabelWithHint: true,
              ),
              onChanged: _cubit.change,
            ),
            if (widget.administrativeCorrection)
              TextField(
                controller: _reason,
                enabled: !state.busy,
                maxLength: 500,
                decoration: InputDecoration(
                  labelText: academicText(context, 'Correction reason'),
                  helperText: academicText(
                    context,
                    'Required if you do not hold this teaching allocation.',
                  ),
                ),
              ),
            Wrap(
              spacing: 12,
              runSpacing: 8,
              children: [
                FilledButton(
                  onPressed: state.busy || !state.dirty || state.conflict
                      ? null
                      : () => _cubit.save(
                          reason: _reason.text.isEmpty ? null : _reason.text,
                        ),
                  child: Text(state.busy ? 'Saving…' : 'Save comment'),
                ),
                OutlinedButton(
                  onPressed: state.busy || _reviewing ? null : _compare,
                  child: const AcademicText('Compare with saved'),
                ),
              ],
            ),
            if (!state.dirty && !state.busy)
              const Padding(
                padding: EdgeInsets.only(top: 12),
                child: AcademicText('Comment matches the saved version.'),
              ),
          ],
        ),
      ),
    ),
  );
}
