import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

import '../localization/academic_text.dart';
import '../wellbeing/school_wall_time.dart';
import 'consultation_repository.dart';

/// Current audience ballots only; anonymous drafts never enter disk or navigation.
class ConsultationsScreen extends StatefulWidget {
  const ConsultationsScreen({
    required this.repository,
    this.initialId,
    super.key,
  });
  final ConsultationRepository repository;
  final int? initialId;
  @override
  State<ConsultationsScreen> createState() => _ConsultationsScreenState();
}

class _ConsultationsScreenState extends State<ConsultationsScreen>
    with WidgetsBindingObserver {
  ConsultationAccess? _access;
  ConsultationPage? _listing;
  ConsultationBallot? _ballot;
  ConsultationResults? _results;
  final _text = <int, TextEditingController>{};
  final _choices = <int, Set<int>>{};
  int _epoch = 0;
  int? _selected;
  bool _visible = true, _busy = false, _denied = false, _uncertain = false;
  String? _message;
  String l(String text) => academicText(context, text);
  bool _current(int epoch) =>
      mounted &&
      _visible &&
      !_denied &&
      epoch == _epoch &&
      widget.repository.isActive;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    final id = widget.initialId;
    if (id == null) {
      _loadListing();
    } else {
      _loadBallot(id);
    }
  }

  void _dropDraft() {
    final old = _text.values.toList();
    for (final controller in old) {
      controller.clear();
    }
    _text.clear();
    _choices.clear();
    // EditableText first detaches the previous frame's controllers.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      for (final controller in old) {
        controller.dispose();
      }
    });
  }

  void _erase() {
    _access = null;
    _listing = null;
    _ballot = null;
    _results = null;
    _selected = null;
    _uncertain = false;
    _dropDraft();
  }

  @override
  void dispose() {
    _epoch++;
    WidgetsBinding.instance.removeObserver(this);
    for (final controller in _text.values) {
      controller.clear();
      controller.dispose();
    }
    _text.clear();
    _choices.clear();
    _access = null;
    _listing = null;
    _ballot = null;
    _results = null;
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      if (mounted) {
        setState(() {
          _visible = false;
          _busy = false;
          _message = null;
          _erase();
        });
      }
    } else if (mounted && !_denied) {
      setState(() => _visible = true);
      // Fresh server access/listing before any body is rendered again.
      _loadListing();
    }
  }

  Future<void> _run(Future<void> Function(int epoch) action) async {
    if (!_visible || _denied || !widget.repository.isActive) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _message = null;
    });
    try {
      await action(epoch);
    } catch (error) {
      if (!_current(epoch)) return;
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        _erase();
        _denied = true;
        _message = l('Your consultation access changed. Reopen this screen.');
      } else {
        _message = l('This consultation could not be loaded. Try again.');
      }
    } finally {
      if (mounted && _visible && epoch == _epoch) setState(() => _busy = false);
    }
  }

  Future<void> _loadListing({int page = 1}) async {
    _erase();
    await _run((epoch) async {
      final access = await widget.repository.access();
      if (!_current(epoch)) return;
      final listing = await widget.repository.listing(page: page);
      if (!_current(epoch)) return;
      _access = access;
      _listing = listing;
    });
  }

  Future<void> _loadBallot(int id, {bool preserveDraft = false}) async {
    if (!preserveDraft) {
      _dropDraft();
      _uncertain = false;
    }
    _selected = id;
    _listing = null;
    _ballot = null;
    _results = null;
    await _run((epoch) async {
      final access = await widget.repository.access();
      if (!_current(epoch)) return;
      final ballot = await widget.repository.ballot(id);
      if (!_current(epoch)) return;
      _access = access;
      _ballot = ballot;
      _uncertain = false;
      if (ballot.participated) {
        _dropDraft();
        _message = l(
          'Your response is recorded. You can participate only once.',
        );
      } else {
        for (final question in ballot.questions) {
          if (question.kind == ConsultationQuestionKindEnum.text) {
            _text.putIfAbsent(question.id, TextEditingController.new);
          }
        }
      }
    });
  }

  bool get _supported =>
      _ballot != null &&
      _ballot!.type != ConsultationBallotTypeEnum.unknownDefaultOpenApi &&
      _ballot!.questions.every(
        (question) =>
            question.kind != ConsultationQuestionKindEnum.unknownDefaultOpenApi,
      );
  bool get _canVote =>
      !_busy &&
      !_uncertain &&
      !_denied &&
      _supported &&
      _access?.writable == true &&
      _ballot?.writable == true &&
      _ballot?.open == true &&
      _ballot?.participated == false;
  Future<void> _vote() async {
    if (!_canVote) return;
    final ballot = _ballot!;
    final answers = <ConsultationVoteAnswer>[];
    for (final question in ballot.questions) {
      final selected = (_choices[question.id] ?? {}).toList()..sort();
      final text = _text[question.id]?.text.trim() ?? '';
      if ((question.required_ &&
              (question.kind == ConsultationQuestionKindEnum.text
                  ? text.isEmpty
                  : selected.isEmpty)) ||
          selected.length > question.maxChoices) {
        setState(
          () => _message = l(
            'Check the required answers and permitted number of choices.',
          ),
        );
        return;
      }
      answers.add(
        ConsultationVoteAnswer(
          questionId: question.id,
          optionIds: question.kind == ConsultationQuestionKindEnum.text
              ? null
              : selected,
          text: question.kind == ConsultationQuestionKindEnum.text
              ? text
              : null,
        ),
      );
    }
    await _run((epoch) async {
      try {
        final receipt = await widget.repository.vote(ballot.id, answers);
        if (!_current(epoch)) return;
        if (!receipt.participated) {
          throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
        }
        _dropDraft();
        _ballot = ConsultationBallot.fromJson({
          ...ballot.toJson(),
          'participated': true,
        });
        _uncertain = false;
        _message = l(
          'Your response is recorded. You can participate only once.',
        );
      } catch (error) {
        if (!_current(epoch)) return;
        if (error is ApiException &&
            [401, 403, 404].contains(error.statusCode)) {
          rethrow;
        }
        if (error is ApiException && error.statusCode == 422) {
          _message = l(
            'Check the required answers and permitted number of choices.',
          );
        } else if (error is ApiException && error.statusCode == 429) {
          _message = l(
            'Too many requests. Wait and refresh this consultation.',
          );
        } else {
          _uncertain = true;
          _message = l(
            'Your submission outcome is unknown. Check participation before trying again.',
          );
        }
      }
    });
  }

  Future<void> _showResults() => _run((epoch) async {
    final id = _selected;
    if (id == null) return;
    final result = await widget.repository.results(id);
    if (_current(epoch)) _results = result;
  });
  String _time(DateTime instant) {
    final zone = _access?.timezone;
    if (zone == null) return '';
    try {
      return '${SchoolWallTime.local(instant, zone).replaceFirst('T', ' ')} ($zone)';
    } on FormatException {
      return '${instant.toUtc().toIso8601String()} (UTC)';
    }
  }

  Widget _choice(ConsultationQuestion question, ConsultationOption option) {
    final checked = _choices[question.id]?.contains(option.id) ?? false;
    final enabled =
        !_busy &&
        !_uncertain &&
        _ballot?.open == true &&
        _ballot?.writable == true &&
        _access?.writable == true;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CheckboxListTile(
          key: ValueKey('consultation-option-${option.id}'),
          value: checked,
          controlAffinity: ListTileControlAffinity.leading,
          title: Text(option.label),
          onChanged: !enabled
              ? null
              : (value) => setState(() {
                  if (question.kind == ConsultationQuestionKindEnum.single) {
                    _choices[question.id] = value == true ? {option.id} : {};
                  } else {
                    final values = _choices.putIfAbsent(
                      question.id,
                      () => <int>{},
                    );
                    if (value == true) {
                      values.add(option.id);
                    } else {
                      values.remove(option.id);
                    }
                  }
                }),
        ),
        if ((option.statement ?? '').isNotEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(option.statement!),
          ),
      ],
    );
  }

  Widget _ballotView(ConsultationBallot ballot) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(ballot.title, style: Theme.of(context).textTheme.headlineSmall),
      if (ballot.instructions.isNotEmpty) Text(ballot.instructions),
      Text(
        l(
          ballot.anonymous
              ? 'Your participation is kept apart from anonymous answers.'
              : 'This consultation records your name.',
        ),
      ),
      Text('${l('Opens')}: ${_time(ballot.opensAt)}'),
      Text('${l('Closes')}: ${_time(ballot.closesAt)}'),
      if (!ballot.writable || _access?.writable != true)
        Text(l('This consultation is read only.')),
      if (!ballot.open) Text(l('This consultation is not open for responses.')),
      if (!_supported)
        Text(l('This consultation needs a newer version of the app.')),
      if (ballot.participated)
        Text(l('Your response is recorded. You can participate only once.')),
      if (!ballot.participated && _supported)
        for (final question in ballot.questions)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    question.label,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  if (question.required_) Text(l('Answer required')),
                  if (question.kind == ConsultationQuestionKindEnum.multiple)
                    Text('${l('Maximum choices')}: ${question.maxChoices}'),
                  if (question.kind == ConsultationQuestionKindEnum.text)
                    TextField(
                      key: ValueKey('consultation-answer-${question.id}'),
                      controller: _text[question.id],
                      maxLength: 2000,
                      minLines: 2,
                      maxLines: 5,
                      enabled:
                          !_busy &&
                          !_uncertain &&
                          ballot.open &&
                          ballot.writable &&
                          _access?.writable == true,
                      decoration: InputDecoration(labelText: l('Your answer')),
                    )
                  else
                    for (final option in question.options)
                      _choice(question, option),
                ],
              ),
            ),
          ),
      if (_uncertain)
        FilledButton(
          onPressed: _busy
              ? null
              : () => _loadBallot(ballot.id, preserveDraft: true),
          child: Text(l('Check whether my response was recorded')),
        ),
      if (!ballot.participated && !_uncertain) ...[
        Text(
          l(
            'Review your answers before submitting. A recorded response cannot be changed.',
          ),
        ),
        FilledButton(
          onPressed: _canVote ? _vote : null,
          child: Text(l('Record my response')),
        ),
      ],
      if (ballot.resultsVisibility ==
              ConsultationBallotResultsVisibilityEnum.publicLive ||
          ballot.status == ConsultationBallotStatusEnum.closed ||
          !ballot.closesAt.isAfter(DateTime.now().toUtc()))
        OutlinedButton(
          onPressed: _busy ? null : _showResults,
          child: Text(l('View consultation results')),
        ),
    ],
  );
  Widget _resultsView(ConsultationResults result) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(
        l(result.closed ? 'Closed results' : 'Live aggregate counts'),
        style: Theme.of(context).textTheme.headlineSmall,
      ),
      Text('${l('Responses recorded')}: ${result.responseCount}'),
      for (final question in result.questions)
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  question.label,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                Text(
                  '${l('Answered')}: ${question.answered} / ${result.responseCount}',
                ),
                if (question.kind == ConsultationResultQuestionKindEnum.text)
                  Text(
                    l(
                      'Individual text answers are available only to consultation managers after close.',
                    ),
                  )
                else ...[
                  if (question.kind ==
                      ConsultationResultQuestionKindEnum.multiple)
                    Text(
                      l(
                        'Participants could select several choices; percentages need not total 100%.',
                      ),
                    ),
                  for (final option in question.options)
                    Builder(
                      builder: (context) {
                        final matches = question.counts.where(
                          (entry) => entry.optionId == option.id,
                        );
                        final count = matches.isEmpty ? 0 : matches.first.count;
                        return Padding(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Text(
                                '${option.label}: $count / ${question.answered}',
                              ),
                              Semantics(
                                label: option.label,
                                value: '$count / ${question.answered}',
                                child: LinearProgressIndicator(
                                  value: question.answered == 0
                                      ? 0
                                      : (count / question.answered)
                                            .clamp(0, 1)
                                            .toDouble(),
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                ],
              ],
            ),
          ),
        ),
      OutlinedButton(
        onPressed: _busy || _selected == null
            ? null
            : () => _loadBallot(_selected!, preserveDraft: true),
        child: Text(l('Back to ballot')),
      ),
    ],
  );
  @override
  Widget build(BuildContext context) {
    if (!_visible || !widget.repository.isActive) {
      return const SizedBox.shrink();
    }
    final ballot = _ballot, results = _results, listing = _listing;
    return Scaffold(
      appBar: AppBar(title: Text(l('Polls, surveys and elections'))),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_busy) const LinearProgressIndicator(),
          if (_message != null)
            Semantics(liveRegion: true, child: Text(_message!)),
          if (!_denied) ...[
            if (_selected != null)
              TextButton(
                onPressed: _busy ? null : () => _loadListing(),
                child: Text(l('All consultations')),
              ),
            if (results != null)
              _resultsView(results)
            else if (ballot != null)
              _ballotView(ballot)
            else if (listing != null) ...[
              if (listing.items.isEmpty)
                Text(
                  l('No consultations are available in your current audience.'),
                ),
              for (final item in listing.items)
                Card(
                  child: ListTile(
                    title: Text(item.title),
                    subtitle: Text(
                      '${_time(item.opensAt)} – ${_time(item.closesAt)}',
                    ),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: _busy ? null : () => _loadBallot(item.id),
                  ),
                ),
              Wrap(
                alignment: WrapAlignment.spaceBetween,
                spacing: 8,
                runSpacing: 8,
                children: [
                  TextButton(
                    onPressed: _busy || listing.page <= 1
                        ? null
                        : () => _loadListing(page: listing.page - 1),
                    child: Text(l('Previous page')),
                  ),
                  TextButton(
                    onPressed: _busy || listing.page >= listing.lastPage
                        ? null
                        : () => _loadListing(page: listing.page + 1),
                    child: Text(l('Next page')),
                  ),
                ],
              ),
            ],
            if (!_busy && !_uncertain)
              TextButton(
                onPressed: () => _selected == null
                    ? _loadListing(page: listing?.page ?? 1)
                    : _loadBallot(_selected!, preserveDraft: true),
                child: Text(l('Refresh consultations')),
              ),
          ],
        ],
      ),
    );
  }
}
