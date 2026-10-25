import 'dart:convert';
import 'dart:typed_data';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import 'transfer_repository.dart';

class TransferConsentScreen extends StatefulWidget {
  const TransferConsentScreen({
    required this.repository,
    this.saveArchive,
    super.key,
  });
  final TransferRepository repository;
  final Future<void> Function(Uint8List bytes)? saveArchive;
  @override
  State<TransferConsentScreen> createState() => _TransferConsentScreenState();
}

class _TransferConsentScreenState extends State<TransferConsentScreen>
    with WidgetsBindingObserver {
  final _scroll = ScrollController();
  TransferAccess? _access;
  TransferPage? _listing;
  TransferDetail? _record;
  int _epoch = 0, _page = 1, _marksLimit = 25, _attendanceLimit = 50;
  int? _selected;
  bool _busy = true, _suspended = false, _approved = false, _withdraw = false;
  String? _error, _pendingAction, _pendingPayload;
  bool get _active => mounted && !_suspended && widget.repository.isActive;
  bool get _editable =>
      _active && !_busy && _pendingPayload == null && _access?.canWrite == true;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  void _top() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && _scroll.hasClients) _scroll.jumpTo(0);
    });
  }

  void _erase() {
    _marksLimit = 25;
    _attendanceLimit = 50;
    _access = null;
    _listing = null;
    _record = null;
    _approved = false;
    _withdraw = false;
    _pendingAction = null;
    _pendingPayload = null;
  }

  @override
  void dispose() {
    _epoch++;
    _erase();
    _scroll.dispose();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant TransferConsentScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository) {
      _selected = null;
      _page = 1;
      _load();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      if (mounted) {
        setState(() {
          _suspended = true;
          _erase();
        });
      }
    } else if (mounted) {
      setState(() => _suspended = false);
      _load();
    }
  }

  Future<void> _load({String? message}) async {
    final epoch = ++_epoch, selected = _selected;
    setState(() {
      _erase();
      _busy = true;
      _error = message;
    });
    try {
      final access = await widget.repository.access();
      if (!_active || epoch != _epoch) return;
      if (!access.guardian) {
        throw ApiException('Guardian access required', statusCode: 403);
      }
      final listing = await widget.repository.consents(page: _page);
      if (!_active || epoch != _epoch) return;
      final record = selected == null
          ? null
          : await widget.repository.detail(selected);
      if (!_active || epoch != _epoch) return;
      setState(() {
        _access = access;
        _listing = listing;
        _record = record;
        _busy = false;
      });
      _top();
    } catch (_) {
      if (mounted && epoch == _epoch) {
        setState(() {
          _erase();
          _busy = false;
          _error =
              'Transfers are unavailable. Check your guardian access and retry.';
        });
        _top();
      }
    }
  }

  Future<void> _open(int id) async {
    if (_busy || _pendingPayload != null) return;
    _selected = id;
    await _load();
  }

  Future<void> _write(String action) async {
    final record = _record;
    if (record == null || !_active || _busy) return;
    if (_pendingPayload == null) {
      if (!_editable ||
          action == 'consent' && (!record.canConsent || !_approved) ||
          action == 'withdraw' && (!record.canWithdraw || !_withdraw)) {
        return;
      }
      _pendingAction = action;
      _pendingPayload = jsonEncode({
        'operation_id': const Uuid().v4(),
        'revision': record.revision,
        if (action == 'consent') ...{
          'fingerprint': record.fingerprint,
          'version': record.consentVersion,
          'confirmation': true,
        },
      });
    }
    final payload = _pendingPayload!, pendingAction = _pendingAction!;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.repository.write(
        record.id,
        pendingAction,
        Map<String, dynamic>.from(jsonDecode(payload) as Map),
      );
      if (!_active || epoch != _epoch) return;
      await _load(message: 'School transfer change saved');
    } catch (error) {
      if (!_active || epoch != _epoch) return;
      final status = error is ApiException ? error.statusCode : null;
      if (status == 409 || status == 422) {
        await _load(
          message:
              'The transfer changed. Review its current records before trying again.',
        );
      } else if (status == 401 || status == 403 || status == 404) {
        setState(() {
          _erase();
          _busy = false;
          _error = 'Your access changed. Reopen School transfers.';
        });
      } else {
        // A lost response can follow a committed consent. Retry its immutable
        // UUID/revision/fingerprint instead of manufacturing a second intent.
        setState(() {
          _busy = false;
          _error = 'The result is unknown. Retry the same transfer action.';
        });
        _top();
      }
    }
  }

  Future<void> _archive() async {
    final record = _record;
    if (!_active ||
        _busy ||
        _pendingPayload != null ||
        record?.canDownload != true) {
      return;
    }
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final bytes = await widget.repository.archive(record!.id);
      if (!_active || epoch != _epoch) return;
      // Export only after an explicit tap and fresh authorization. No archive
      // enters Hive, app storage or a background/offline cache.
      if (widget.saveArchive != null) {
        await widget.saveArchive!(bytes);
      } else {
        if (!mounted) return;
        await FilePicker.saveFile(
          dialogTitle: academicText(context, 'Save signed transfer archive'),
          fileName: 'school-transfer.zip',
          mimeType: 'application/zip',
          bytes: bytes,
          type: FileType.custom,
          allowedExtensions: ['zip'],
        );
      }
    } catch (error) {
      if (_active && epoch == _epoch) {
        final status = error is ApiException ? error.statusCode : null;
        setState(() {
          if (status == 401 || status == 403 || status == 404) _erase();
          _error =
              'The archive is unavailable. Refresh the transfer and try again.';
        });
        _top();
      }
    } finally {
      if (_active && epoch == _epoch) setState(() => _busy = false);
    }
  }

  Widget _fact(String label, String value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 6),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AcademicText(label, style: Theme.of(context).textTheme.labelLarge),
        SelectableText(value),
      ],
    ),
  );
  Widget _heading(String value) => Padding(
    padding: const EdgeInsets.only(top: 20, bottom: 8),
    child: AcademicText(value, style: Theme.of(context).textTheme.titleMedium),
  );
  Widget _detail(TransferDetail r) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(r.learnerName, style: Theme.of(context).textTheme.headlineSmall),
      _fact('Sending school', r.fromSchoolName),
      _fact('Receiving school', r.toSchoolName),
      _fact('Consenting guardian', r.guardianName),
      _fact(
        'Student number',
        r.studentNumber ?? academicText(context, 'Not available'),
      ),
      _fact('Last attended date', r.lastAttendedOn),
      _fact('Receiving start date', r.startsOn),
      AcademicText('Transfer state: ${r.state.value}'),
      if (r.exportOnly)
        const AcademicText(
          'Historical export only. This copy does not close a current enrolment.',
        ),
      if (r.expiresAt != null)
        _fact('Handoff copy expires', r.expiresAt!.toLocal().toString()),
      _fact('Package fingerprint', r.fingerprint),
      _heading('Released reports'),
      if (r.reports.isEmpty) const AcademicText('No selected reports.'),
      for (final report in r.reports)
        Text('${report.yearName} · ${report.termName}'),
      _heading('Shared documents'),
      if (r.documents.isEmpty) const AcademicText('No selected documents.'),
      for (final document in r.documents) Text(document.name),
      _heading('Historical released marks'),
      if (r.releasedMarks.isEmpty) const AcademicText('No released marks.'),
      for (final mark in r.releasedMarks.take(_marksLimit))
        ListTile(
          contentPadding: EdgeInsets.zero,
          title: Text(mark.subjectName),
          subtitle: Text(mark.releasedAt.toIso8601String().substring(0, 10)),
          trailing: Text(
            mark.percentageBp == null
                ? academicText(context, 'Not available')
                : '${(mark.percentageBp! / 100).toStringAsFixed(2)}%',
          ),
        ),
      if (_marksLimit < r.releasedMarks.length)
        TextButton(
          onPressed: !_busy ? () => setState(() => _marksLimit += 25) : null,
          child: const AcademicText('Show more released marks'),
        ),
      _heading('Dated attendance'),
      if (r.attendance.isEmpty) const AcademicText('No dated attendance.'),
      for (final entry in r.attendance.take(_attendanceLimit))
        ListTile(
          contentPadding: EdgeInsets.zero,
          title: Text(entry.onDate),
          subtitle: AcademicText(
            entry.kind == 'homeroom' ? 'Homeroom' : 'Lesson',
          ),
          trailing: AcademicText('Attendance state: ${entry.state}'),
        ),
      if (_attendanceLimit < r.attendance.length)
        TextButton(
          onPressed: !_busy
              ? () => setState(() => _attendanceLimit += 50)
              : null,
          child: const AcademicText('Show more attendance'),
        ),
      if (r.canDownload)
        OutlinedButton.icon(
          onPressed: !_busy && _pendingPayload == null ? _archive : null,
          icon: const Icon(Icons.download_outlined),
          label: const AcademicText('Download signed transfer archive'),
        ),
      if (r.canConsent) ...[
        CheckboxListTile(
          contentPadding: EdgeInsets.zero,
          title: const AcademicText(
            'I reviewed these records, the named receiving school and dates, and consent to this exact package.',
          ),
          value: _approved,
          onChanged: _editable
              ? (value) => setState(() => _approved = value == true)
              : null,
        ),
        FilledButton(
          onPressed: _editable && _approved ? () => _write('consent') : null,
          child: const AcademicText('Consent to this transfer'),
        ),
      ],
      if (r.canWithdraw) ...[
        CheckboxListTile(
          contentPadding: EdgeInsets.zero,
          title: const AcademicText(
            'I want to withdraw this pending transfer.',
          ),
          value: _withdraw,
          onChanged: _editable
              ? (value) => setState(() => _withdraw = value == true)
              : null,
        ),
        OutlinedButton(
          onPressed: _editable && _withdraw ? () => _write('withdraw') : null,
          child: const AcademicText('Withdraw transfer consent'),
        ),
      ],
      const SizedBox(height: 16),
      const AcademicText(
        'Transferred marks remain historical source records. The receiving school handles learner approval separately.',
      ),
    ],
  );

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: academicText(context, 'School transfers'),
    body: !_active
        ? const Center(
            child: AcademicText(
              'Your access changed. Reopen School transfers.',
            ),
          )
        : ListView(
            controller: _scroll,
            padding: const EdgeInsets.all(16),
            children: [
              if (_busy) const LinearProgressIndicator(),
              if (_error != null)
                Semantics(liveRegion: true, child: AcademicText(_error!)),
              if (_pendingPayload != null && !_busy)
                FilledButton(
                  onPressed: () => _write(_pendingAction!),
                  child: const AcademicText('Retry the same transfer action'),
                ),
              if (_pendingPayload == null)
                Align(
                  alignment: AlignmentDirectional.centerEnd,
                  child: IconButton(
                    tooltip: academicText(context, 'Refresh transfers'),
                    onPressed: _busy ? null : _load,
                    icon: const Icon(Icons.refresh),
                  ),
                ),
              if (_record != null) ...[
                TextButton.icon(
                  onPressed: _busy || _pendingPayload != null
                      ? null
                      : () {
                          _selected = null;
                          _load();
                        },
                  icon: const Icon(Icons.arrow_back),
                  label: const AcademicText('All transfer requests'),
                ),
                _detail(_record!),
              ] else if (_listing != null) ...[
                const AcademicText(
                  'Review the destination and records before giving consent.',
                ),
                for (final row in _listing!.items)
                  Card(
                    child: ListTile(
                      title: Text(row.learnerName),
                      subtitle: Text(
                        '${row.toSchoolName}\n${row.startsOn}\n${academicText(context, 'Transfer state: ${row.state.value}')}',
                      ),
                      isThreeLine: true,
                      trailing: const Icon(Icons.chevron_right),
                      onTap: _busy ? null : () => _open(row.id),
                    ),
                  ),
                if (_listing!.items.isEmpty)
                  const AcademicText('No transfers in this view.'),
                Wrap(
                  spacing: 12,
                  children: [
                    if (_page > 1)
                      TextButton(
                        onPressed: _busy
                            ? null
                            : () {
                                _page--;
                                _load();
                              },
                        child: const AcademicText('Previous'),
                      ),
                    if (_listing!.hasMore)
                      TextButton(
                        onPressed: _busy
                            ? null
                            : () {
                                _page++;
                                _load();
                              },
                        child: const AcademicText('Next'),
                      ),
                  ],
                ),
              ],
            ],
          ),
  );
}
