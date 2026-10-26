import 'dart:async';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

import '../localization/academic_text.dart';
import 'tutor_repository.dart';

/// A separate parent projection. This screen cannot fetch conversations/photos.
class TutorSummaryScreen extends StatefulWidget {
  const TutorSummaryScreen({
    required this.repository,
    required this.learnerId,
    super.key,
  });
  final TutorSummaryRepository repository;
  final int learnerId;
  @override
  State<TutorSummaryScreen> createState() => _TutorSummaryScreenState();
}

class _TutorSummaryScreenState extends State<TutorSummaryScreen>
    with WidgetsBindingObserver {
  final Map<int, String> _subjects = {};
  TutorParentSummary? _summary;
  int _epoch = 0, _page = 0, _last = 1;
  bool _busy = true, _suspended = false;
  String? _error;
  bool get _active => mounted && !_suspended && widget.repository.isActive;
  bool _current(int epoch) => _active && epoch == _epoch;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    unawaited(_load());
  }

  void _erase() {
    _subjects.clear();
    _summary = null;
    _page = 0;
    _last = 1;
    _error = null;
  }

  @override
  void dispose() {
    _epoch++;
    _erase();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didUpdateWidget(TutorSummaryScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository ||
        oldWidget.learnerId != widget.learnerId) {
      _epoch++;
      _erase();
      unawaited(_load());
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      setState(() {
        _suspended = true;
        _erase();
      });
    } else {
      _suspended = false;
      unawaited(_load());
    }
  }

  void _failed(Object error) {
    _erase();
    _busy = false;
    _error =
        error is ApiException &&
            error.isApiMessage &&
            ![401, 403, 404].contains(error.statusCode)
        ? error.errorMessage
        : 'Reopen the tutor summary to check your access.';
  }

  Future<void> _load({bool more = false}) async {
    final epoch = ++_epoch;
    if (!_active) return;
    setState(() {
      _busy = true;
      _error = null;
      if (!more) _erase();
    });
    try {
      final result = await widget.repository.subjects(
        widget.learnerId,
        page: _page + 1,
      );
      if (!_current(epoch)) return;
      setState(() {
        for (final item in result.items) {
          _subjects[item.subjectId] = item.subjectName;
        }
        _page = result.page;
        _last = result.lastPage;
        _busy = false;
      });
    } catch (error) {
      if (_current(epoch)) setState(() => _failed(error));
    }
  }

  Future<void> _open(int subject) async {
    if (!_active || _busy) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _summary = null;
      _error = null;
    });
    try {
      final result = await widget.repository.summary(widget.learnerId, subject);
      if (!_current(epoch)) return;
      if (result.learnerId != widget.learnerId ||
          result.subjectId != subject ||
          result.containsRawChat) {
        throw ApiException(
          ApiErrorCodes.defaultErrorMessageKey,
          statusCode: 403,
        );
      }
      setState(() {
        _summary = result;
        _busy = false;
      });
    } catch (error) {
      if (_current(epoch)) setState(() => _failed(error));
    }
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: academicText(context, 'Tutor learning summary'),
    action: IconButton(
      tooltip: academicText(context, 'Refresh'),
      onPressed: _busy ? null : _load,
      icon: const Icon(Icons.refresh),
    ),
    body: !_active
        ? const Center(
            child: AcademicText(
              'Reopen the tutor summary to check your access.',
            ),
          )
        : ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const AcademicText(
                'An overview of learning steps, with one idea to try together.',
              ),
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  child: Text(_error!),
                ),
              for (final entry in _subjects.entries)
                ListTile(
                  title: Text(entry.value),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: _busy ? null : () => _open(entry.key),
                ),
              if (!_busy && _subjects.isEmpty && _error == null)
                const AcademicText(
                  'No published learning subjects are available for this child yet.',
                ),
              if (_page < _last)
                TextButton(
                  onPressed: _busy ? null : () => _load(more: true),
                  child: const AcademicText('More subjects'),
                ),
              if (_summary case final value?)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          value.subjectName,
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 12),
                        const AcademicText('Learning conversations'),
                        Text('${value.learningSessions}'),
                        const AcademicText('Completed learning steps'),
                        Text('${value.learningSteps}'),
                        const SizedBox(height: 12),
                        const AcademicText(
                          'Ask your child to explain one worked example.',
                        ),
                        const SizedBox(height: 8),
                        const AcademicText(
                          'This learning summary does not change school marks.',
                        ),
                      ],
                    ),
                  ),
                ),
              if (_busy) const Center(child: CircularProgressIndicator()),
            ],
          ),
  );
}
