import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'academic_labels.dart';
import 'mark_input.dart';
import 'marks_repository.dart';

class MarkReleaseDialog extends StatefulWidget {
  const MarkReleaseDialog({
    required this.repository,
    required this.group,
    required this.term,
    required this.learners,
    super.key,
  });
  final MarksRepository repository;
  final int group, term;
  final Map<int, String> learners;
  @override
  State<MarkReleaseDialog> createState() => _MarkReleaseDialogState();
}

class _MarkReleaseDialogState extends State<MarkReleaseDialog> {
  AcademicReleaseWriteModeEnum _mode = AcademicReleaseWriteModeEnum.official;
  AcademicReleasePreviewData? _preview;
  String? _error;
  bool _busy = false;
  String l(String key) => academicLabel(context, key);
  Future<void> _load() async {
    setState(() {
      _busy = true;
      _error = null;
      _preview = null;
    });
    try {
      final preview = await widget.repository.releasePreview(
        widget.group,
        AcademicReleaseWrite(
          termId: widget.term,
          mode: _mode,
          learnerIds: widget.learners.keys.toSet(),
        ),
      );
      if (mounted) setState(() => _preview = preview);
    } catch (e) {
      if (mounted) {
        setState(
          () => _error = e is ApiException && e.isApiMessage
              ? e.errorMessage
              : l('loadFailed'),
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _commit() async {
    if (_preview?.canRelease != true || _busy) return;
    final preview = _preview!;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.repository.release(
        widget.group,
        AcademicReleaseCommitWrite.fromJson({
          'term_id': widget.term,
          'mode': _mode.value,
          'learner_ids': widget.learners.keys.toList(),
          'fingerprint': preview.fingerprint,
        }),
      );
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) {
        setState(() {
          _preview = null;
          _error = e is ApiException && e.isApiMessage
              ? e.errorMessage
              : l('loadFailed');
        });
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => PopScope(
    canPop: !_busy,
    child: AlertDialog(
      title: Text(l('release')),
      scrollable: true,
      content: SizedBox(
        width: 560,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(l('releaseHint')),
            const SizedBox(height: 16),
            DropdownButtonFormField<AcademicReleaseWriteModeEnum>(
              initialValue: _mode,
              isExpanded: true,
              decoration: InputDecoration(labelText: l('releaseMode')),
              items: [
                DropdownMenuItem(
                  value: AcademicReleaseWriteModeEnum.official,
                  child: Text(l('official')),
                ),
                DropdownMenuItem(
                  value: AcademicReleaseWriteModeEnum.running,
                  child: Text(l('runningRelease')),
                ),
              ],
              onChanged: _busy
                  ? null
                  : (mode) {
                      if (mode != null) {
                        setState(() {
                          _mode = mode;
                          _preview = null;
                        });
                      }
                    },
            ),
            if (_error != null)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Text(
                  _error!,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            if (_busy)
              const Padding(
                padding: EdgeInsets.all(12),
                child: LinearProgressIndicator(),
              ),
            if (_preview != null) ...[
              const SizedBox(height: 16),
              for (final row in _preview!.learners)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(widget.learners[row.learnerId] ?? l('learner')),
                  subtitle: Text(
                    '${row.ready ? l('ready') : l('incomplete')} · ${l('coverage')}: ${markDecimal(row.coverageBp)}%',
                  ),
                  trailing: Text(
                    row.percentageBp == null
                        ? '—'
                        : '${markDecimal(row.percentageBp!)}%',
                  ),
                ),
            ],
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: _busy ? null : () => Navigator.pop(context, false),
          child: Text(l('cancel')),
        ),
        OutlinedButton(
          onPressed: _busy ? null : _load,
          child: Text(l('preview')),
        ),
        FilledButton(
          onPressed: _busy || _preview?.canRelease != true ? null : _commit,
          child: Text(l('release')),
        ),
      ],
    ),
  );
}
