import 'dart:async';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import 'recognition_repository.dart';

class RecognitionScreen extends StatefulWidget {
  const RecognitionScreen({
    required this.repository,
    required this.modules,
    this.family = false,
    this.learnerId,
    this.learnerName,
    super.key,
  });
  final RecognitionRepository repository;
  final List<String> modules;
  final bool family;
  final int? learnerId;
  final String? learnerName;
  @override
  State<RecognitionScreen> createState() => _RecognitionScreenState();
}

class _RecognitionScreenState extends State<RecognitionScreen>
    with WidgetsBindingObserver {
  final _search = TextEditingController(),
      _reason = TextEditingController(),
      _points = TextEditingController(text: '5');
  final _form = GlobalKey<FormState>();
  String _module = 'house_points';
  int? _learner;
  String? _learnerName;
  RecognitionAccess? _access;
  RecognitionConfiguration? _configuration;
  RecognitionLearnerPage? _choices;
  RecognitionHouseCounters? _counters;
  RecognitionPointHistory? _history;
  RecognitionAwardProfile? _awards;
  RecognitionIssue? _pending;
  int? _category;
  int _epoch = 0, _refreshEpoch = 0, _seenPoint = 0, _seenAward = 0;
  bool _loading = true,
      _busy = false,
      _erased = false,
      _denied = false,
      _unknown = false,
      _reconcile = false;
  String? _error;
  DateTime? _updated;
  Timer? _fallback, _debounce;
  StreamSubscription<RecognitionInvalidation>? _subscription;
  String l(String text) => academicText(context, text);
  bool _active(int epoch) =>
      mounted &&
      epoch == _epoch &&
      !_erased &&
      !_denied &&
      widget.repository.isActive;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _module = widget.modules.contains('house_points')
        ? 'house_points'
        : 'awards';
    _learner = widget.learnerId;
    _learnerName = widget.learnerName;
    _listen();
    _fallback = Timer.periodic(const Duration(seconds: 15), (_) => _refresh());
    unawaited(_load());
  }

  void _listen() {
    _subscription = widget.repository.changes.listen((event) {
      if (event.schoolId != widget.repository.schoolId ||
          (event.learnerId != null && event.learnerId != _learner)) {
        return;
      }
      _debounce?.cancel();
      _debounce = Timer(const Duration(milliseconds: 150), _refresh);
    });
  }

  @override
  void didUpdateWidget(covariant RecognitionScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (identical(widget.repository, oldWidget.repository) &&
        widget.learnerId == oldWidget.learnerId &&
        widget.family == oldWidget.family &&
        listEquals(widget.modules, oldWidget.modules)) {
      return;
    }
    _epoch++;
    _debounce?.cancel();
    unawaited(_subscription?.cancel());
    _clear(forgetLearner: true);
    _learner = widget.learnerId;
    _learnerName = widget.learnerName;
    _module = widget.modules.contains(_module)
        ? _module
        : widget.modules.contains('house_points')
        ? 'house_points'
        : 'awards';
    _erased = false;
    _denied = false;
    _reconcile = false;
    _loading = true;
    _error = null;
    _listen();
    final generation = _epoch;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && generation == _epoch) unawaited(_load());
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _epoch++;
    _pending = null;
    _access = null;
    _configuration = null;
    _history = null;
    _awards = null;
    _choices = null;
    _counters = null;
    _learnerName = null;
    _fallback?.cancel();
    _debounce?.cancel();
    unawaited(_subscription?.cancel());
    _search.dispose();
    _reason.dispose();
    _points.dispose();
    super.dispose();
  }

  void _clear({bool forgetLearner = false}) {
    _access = null;
    _configuration = null;
    _choices = null;
    _counters = null;
    _history = null;
    _awards = null;
    _category = null;
    _updated = null;
    _reason.clear();
    _search.clear();
    _points.text = '5';
    _seenPoint = 0;
    _seenAward = 0;
    if (_pending != null || _busy) _reconcile = true;
    _pending = null;
    _unknown = false;
    _busy = false;
    if (forgetLearner && !widget.family) {
      _learner = null;
      _learnerName = null;
    }
  }

  void _deny() {
    if (!mounted) return;
    _epoch++;
    setState(() {
      _clear();
      _denied = true;
      _erased = true;
      _loading = false;
      _error = null;
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      if (_erased && !_denied) {
        _erased = false;
        unawaited(_load());
      }
      return;
    }
    if (state == AppLifecycleState.inactive ||
        state == AppLifecycleState.hidden ||
        state == AppLifecycleState.paused ||
        state == AppLifecycleState.detached) {
      _epoch++;
      _debounce?.cancel();
      if (mounted) {
        setState(() {
          _clear();
          _erased = true;
          _loading = false;
          _error = null;
        });
      }
    }
  }

  bool _refusal(Object error) =>
      error is ApiException && [401, 403, 404, 423].contains(error.statusCode);
  void _failure(Object error, int epoch) {
    if (!_active(epoch)) return;
    if (_refusal(error)) {
      _deny();
      return;
    }
    setState(() {
      _loading = false;
      _busy = false;
      _error = l('Recognition could not be loaded. Try again.');
    });
  }

  Future<void> _load() async {
    if (!mounted || _erased || _denied) return;
    final epoch = ++_epoch;
    setState(() {
      _loading = true;
      _error = null;
    });
    if (!widget.modules.contains(_module) ||
        !widget.repository.isActive ||
        (widget.family && (_learner == null || _learner! <= 0))) {
      _deny();
      return;
    }
    try {
      final access = await widget.repository.access(_module);
      if (!_active(epoch)) return;
      RecognitionConfiguration? configuration;
      RecognitionLearnerPage? choices;
      if (!widget.family) {
        configuration = await widget.repository.configuration(_module);
        if (!_active(epoch)) return;
        choices = await widget.repository.learners(
          _module,
          search: _search.text.trim(),
        );
        if (!_active(epoch)) return;
      }
      RecognitionHouseCounters? counters;
      RecognitionPointHistory? history;
      RecognitionAwardProfile? awards;
      if (_module == 'house_points') {
        counters = await widget.repository.counters(access.sessionYearId);
        if (!_active(epoch)) return;
        if (_learner != null) {
          history = await widget.repository.points(
            _learner!,
            access.sessionYearId,
            family: widget.family,
          );
          if (!_active(epoch)) return;
        }
      } else if (_learner != null) {
        awards = await widget.repository.awards(
          _learner!,
          family: widget.family,
        );
        if (!_active(epoch)) return;
      }
      setState(() {
        _access = access;
        _configuration = configuration;
        _choices = choices;
        _counters = counters;
        _history = history;
        _awards = awards;
        _seenPoint =
            history?.items.fold<int>(0, (a, b) => a > b.id ? a : b.id) ?? 0;
        _seenAward =
            awards?.items.fold<int>(0, (a, b) => a > b.id ? a : b.id) ?? 0;
        _updated = DateTime.now();
        _loading = false;
      });
    } catch (error) {
      _failure(error, epoch);
    }
  }

  Future<void> _refresh() async {
    if (!mounted ||
        _loading ||
        _busy ||
        _erased ||
        _denied ||
        _access == null) {
      return;
    }
    final epoch = _epoch, refresh = ++_refreshEpoch;
    if (!widget.repository.isActive) {
      _deny();
      return;
    }
    try {
      RecognitionHouseCounters? counters;
      RecognitionPointHistory? history;
      RecognitionAwardProfile? awards;
      if (_module == 'house_points') {
        counters = await widget.repository.counters(_access!.sessionYearId);
        if (!_active(epoch) || refresh != _refreshEpoch) return;
        if (_learner != null) {
          history = await widget.repository.points(
            _learner!,
            _access!.sessionYearId,
            family: widget.family,
          );
          if (!_active(epoch) || refresh != _refreshEpoch) return;
        }
      } else if (_learner != null) {
        awards = await widget.repository.awards(
          _learner!,
          family: widget.family,
        );
        if (!_active(epoch) || refresh != _refreshEpoch) return;
      }
      String? moment;
      if (widget.family && history != null) {
        final fresh = history.items.where(
          (p) => p.id > _seenPoint && p.points > 0 && p.reversesId == null,
        );
        if (fresh.isNotEmpty) {
          final point = fresh.first;
          moment =
              '${point.points} ${l('house points')} · ${_learnerName ?? point.houseName}';
        }
      }
      if (widget.family &&
          awards != null &&
          awards.items.any((p) => p.id > _seenAward)) {
        moment = l('A new award is on your profile.');
      }
      setState(() {
        if (counters != null &&
            (_counters == null || counters.revision >= _counters!.revision)) {
          _counters = counters;
        }
        _history = history;
        _awards = awards;
        _seenPoint =
            history?.items.fold<int>(
              _seenPoint,
              (a, b) => a > b.id ? a : b.id,
            ) ??
            _seenPoint;
        _seenAward =
            awards?.items.fold<int>(
              _seenAward,
              (a, b) => a > b.id ? a : b.id,
            ) ??
            _seenAward;
        _updated = DateTime.now();
      });
      if (moment != null && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Semantics(liveRegion: true, child: Text(moment))),
        );
      }
    } catch (error) {
      if (_active(epoch) && refresh == _refreshEpoch && _refusal(error)) {
        _deny();
      }
    }
  }

  Future<void> _searchMore({int after = 0}) async {
    if (_busy || _loading) return;
    final epoch = _epoch;
    setState(() => _busy = true);
    try {
      final rows = await widget.repository.learners(
        _module,
        search: _search.text.trim(),
        after: after,
      );
      if (!_active(epoch)) return;
      setState(() {
        _choices = rows;
        _busy = false;
      });
    } catch (error) {
      _failure(error, epoch);
    }
  }

  void _choose(int learner, String name) {
    if (_busy || _pending != null) return;
    _epoch++;
    _clear();
    setState(() {
      _learner = learner;
      _learnerName = name;
    });
    unawaited(_load());
  }

  void _switch(String? module) {
    if (module == null || module == _module || _busy || _pending != null) {
      return;
    }
    _epoch++;
    _clear(forgetLearner: true);
    setState(() => _module = module);
    unawaited(_load());
  }

  Future<void> _issue({bool retry = false}) async {
    if (!mounted || !widget.repository.isActive) return;
    if (_busy ||
        _erased ||
        _denied ||
        _access?.canWrite != true ||
        _learner == null ||
        _reconcile) {
      return;
    }
    if (!retry) {
      if (!(_form.currentState?.validate() ?? false) || _category == null) {
        return;
      }
      _pending = RecognitionIssue(
        operationId: const Uuid().v4(),
        learnerId: _learner!,
        sessionYearId: _access!.sessionYearId,
        pointCategoryId: _category!,
        points: int.parse(_points.text.trim()),
        reason: _reason.text.trim(),
        public: false,
      );
    }
    final request = _pending;
    if (request == null) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.repository.issue(request);
      if (!_active(epoch)) return;
      setState(() {
        _busy = false;
        _unknown = false;
        _pending = null;
        _reason.clear();
      });
      await _load();
    } catch (error) {
      if (!_active(epoch)) return;
      if (_refusal(error)) {
        _deny();
        return;
      }
      final unknown =
          error is! ApiException ||
          error.isTransportFailure ||
          error.statusCode == null;
      setState(() {
        _busy = false;
        _unknown = unknown;
        _error = l(
          unknown
              ? 'The outcome is unknown. Retry this exact change or review the latest entries.'
              : 'The change was refused. Review the current record before trying again.',
        );
        if (!unknown) _pending = null;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_error!),
            duration: const Duration(seconds: 8),
            action: unknown
                ? SnackBarAction(
                    label: l('Retry exact change'),
                    onPressed: () => _issue(retry: true),
                  )
                : null,
          ),
        );
      }
    }
  }

  Future<void> _older() async {
    if (_busy || _access == null || _learner == null) return;
    final epoch = _epoch;
    setState(() => _busy = true);
    try {
      if (_module == 'house_points' && _history?.nextBefore != null) {
        final next = await widget.repository.points(
          _learner!,
          _access!.sessionYearId,
          family: widget.family,
          before: _history!.nextBefore,
        );
        if (!_active(epoch)) return;
        setState(
          () => _history = RecognitionPointHistory(
            items: [..._history!.items, ...next.items],
            nextBefore: next.nextBefore,
          ),
        );
      } else if (_module == 'awards' && _awards?.nextBefore != null) {
        final next = await widget.repository.awards(
          _learner!,
          family: widget.family,
          before: _awards!.nextBefore,
        );
        if (!_active(epoch)) return;
        setState(
          () => _awards = RecognitionAwardProfile(
            items: [..._awards!.items, ...next.items],
            nextBefore: next.nextBefore,
          ),
        );
      }
      if (_active(epoch)) setState(() => _busy = false);
    } catch (error) {
      _failure(error, epoch);
    }
  }

  Color _colour(String hex, Color fallback) {
    if (!RegExp(r'^#[a-fA-F0-9]{6}$').hasMatch(hex)) return fallback;
    return Color(0xff000000 | int.parse(hex.substring(1), radix: 16));
  }

  IconData _emblem(String name) => switch (name) {
    'star' => Icons.star_outline,
    'mountain' => Icons.landscape_outlined,
    'river' => Icons.waves,
    'forest' => Icons.park_outlined,
    'sun' => Icons.wb_sunny_outlined,
    _ => Icons.shield_outlined,
  };
  @override
  Widget build(BuildContext context) {
    if (!widget.repository.isActive && !_denied) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _deny();
      });
    }
    final redacted = _erased || _denied || !widget.repository.isActive;
    return ScreenScaffold(
      title: l('Houses and awards'),
      action: IconButton(
        tooltip: l('Refresh recognition'),
        onPressed: redacted || _loading || _busy ? null : _load,
        icon: const Icon(Icons.refresh),
      ),
      body: redacted
          ? Center(
              child: AcademicText(
                _denied
                    ? 'Your recognition access changed. Reopen this screen.'
                    : 'Recognition is hidden while this app is in the background.',
              ),
            )
          : _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (widget.modules.length > 1)
                  DropdownButtonFormField<String>(
                    initialValue: _module,
                    isExpanded: true,
                    decoration: InputDecoration(
                      labelText: l('Recognition view'),
                    ),
                    items: [
                      for (final module in widget.modules)
                        DropdownMenuItem(
                          value: module,
                          child: Text(
                            l(
                              module == 'house_points'
                                  ? 'House points'
                                  : 'Permanent awards',
                            ),
                          ),
                        ),
                    ],
                    onChanged: _busy || _pending != null ? null : _switch,
                  ),
                if (_learnerName != null)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      _learnerName!,
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                  ),
                if (_error != null)
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Semantics(liveRegion: true, child: Text(_error!)),
                    ),
                  ),
                if (_unknown && _pending != null)
                  Wrap(
                    spacing: 8,
                    children: [
                      FilledButton(
                        onPressed: _busy ? null : () => _issue(retry: true),
                        child: AcademicText('Retry exact point change'),
                      ),
                      TextButton(
                        onPressed: _busy
                            ? null
                            : () async {
                                setState(() {
                                  _pending = null;
                                  _unknown = false;
                                  _reconcile = true;
                                });
                                await _load();
                              },
                        child: AcademicText('Review latest entries'),
                      ),
                    ],
                  ),
                if (_reconcile)
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const AcademicText(
                            'A previous change may have completed. Review the entries before starting another.',
                          ),
                          TextButton(
                            onPressed: _busy
                                ? null
                                : () => setState(() => _reconcile = false),
                            child: const AcademicText(
                              'I reviewed the latest entries',
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                if (!widget.family) ...[
                  TextField(
                    controller: _search,
                    enabled: !_busy && _pending == null,
                    maxLength: 100,
                    decoration: InputDecoration(
                      labelText: l('Find a learner'),
                      suffixIcon: IconButton(
                        tooltip: l('Search learners'),
                        onPressed: _busy ? null : () => _searchMore(),
                        icon: const Icon(Icons.search),
                      ),
                    ),
                    onSubmitted: (_) => _searchMore(),
                  ),
                  for (final person
                      in _choices?.items ?? <RecognitionLearner>[])
                    ListTile(
                      title: Text(person.name),
                      selected: _learner == person.id,
                      onTap: _busy || _pending != null
                          ? null
                          : () => _choose(person.id, person.name),
                      trailing: const Icon(Icons.chevron_right),
                    ),
                  if (_choices?.nextAfter != null)
                    TextButton(
                      onPressed: _busy
                          ? null
                          : () => _searchMore(after: _choices!.nextAfter!),
                      child: const AcademicText('More learners'),
                    ),
                ],
                if (_module == 'house_points') ...[
                  if (_counters != null)
                    LayoutBuilder(
                      builder: (context, constraints) => Wrap(
                        spacing: 12,
                        runSpacing: 12,
                        children: [
                          for (final house in _counters!.items)
                            SizedBox(
                              width: constraints.maxWidth > 650
                                  ? (constraints.maxWidth - 24) / 3
                                  : constraints.maxWidth,
                              child: Card(
                                child: Padding(
                                  padding: const EdgeInsets.all(16),
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Icon(
                                        _emblem(house.emblem),
                                        color: _colour(
                                          house.primaryColour,
                                          Theme.of(context).colorScheme.primary,
                                        ),
                                      ),
                                      Text(
                                        house.name,
                                        style: Theme.of(
                                          context,
                                        ).textTheme.titleLarge,
                                      ),
                                      Semantics(
                                        liveRegion: true,
                                        child: Text(
                                          '${house.points}',
                                          style: Theme.of(
                                            context,
                                          ).textTheme.displaySmall,
                                        ),
                                      ),
                                      const AcademicText('House points'),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                        ],
                      ),
                    ),
                  if (!widget.family &&
                      _learner != null &&
                      _configuration != null &&
                      _access?.canWrite == true)
                    _issueForm(),
                  if (_history != null) ...[
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      child: Text(
                        l('House point history for this year'),
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                    ),
                    if (_history!.items.isEmpty)
                      const AcademicText(
                        'No house points have been recorded in this year.',
                      ),
                    for (final point in _history!.items)
                      Card(
                        child: ListTile(
                          title: Text(
                            '${point.points} · ${point.categoryName}',
                          ),
                          subtitle: Text(
                            '${point.houseName} · ${point.onDate}\n${point.reason}',
                          ),
                          isThreeLine: true,
                        ),
                      ),
                    if (_history!.nextBefore != null)
                      TextButton(
                        onPressed: _busy ? null : _older,
                        child: const AcademicText('Older point entries'),
                      ),
                  ],
                ] else if (_awards != null) ...[
                  const AcademicText(
                    'Award presentations stay on the permanent profile. House point history remains separate.',
                  ),
                  if (_awards!.items.isEmpty)
                    const AcademicText(
                      'No award presentations have been recorded yet.',
                    ),
                  for (final award in _awards!.items)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              award.title,
                              style: Theme.of(context).textTheme.titleLarge,
                            ),
                            Text(award.presentedOn),
                            Text(award.criteria),
                            if (award.event != null) Text(award.event!),
                            if (award.outsideOrganisation != null)
                              Text(award.outsideOrganisation!),
                          ],
                        ),
                      ),
                    ),
                  if (_awards!.nextBefore != null)
                    TextButton(
                      onPressed: _busy ? null : _older,
                      child: const AcademicText('Older awards'),
                    ),
                ],
                if (_updated != null)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      '${l('Last updated')}: ${TimeOfDay.fromDateTime(_updated!).format(context)}',
                    ),
                  ),
              ],
            ),
    );
  }

  Widget _issueForm() {
    final categories =
        _configuration!.pointCategories?.where((c) => c.active).toList() ??
        <RecognitionPointCategory>[];
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _form,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                l('Award house points'),
                style: Theme.of(context).textTheme.titleLarge,
              ),
              DropdownButtonFormField<int>(
                initialValue: _category,
                isExpanded: true,
                decoration: InputDecoration(labelText: l('Point category')),
                items: [
                  for (final category in categories)
                    DropdownMenuItem(
                      value: category.id,
                      child: Text(category.name),
                    ),
                ],
                onChanged: _busy || _pending != null || _reconcile
                    ? null
                    : (id) {
                        setState(() => _category = id);
                        if (id != null) {
                          _points.text =
                              '${categories.firstWhere((c) => c.id == id).defaultPoints}';
                        }
                      },
                validator: (value) =>
                    value == null ? l('Choose a point category.') : null,
              ),
              TextFormField(
                controller: _points,
                enabled: !_busy && _pending == null && !_reconcile,
                keyboardType: const TextInputType.numberWithOptions(
                  signed: true,
                ),
                decoration: InputDecoration(labelText: l('Points')),
                validator: (value) {
                  final number = int.tryParse(value?.trim() ?? '');
                  if (number == null ||
                      number == 0 ||
                      number < -10000 ||
                      number > 10000) {
                    return l('Enter a non-zero whole number of points.');
                  }
                  if (_category != null) {
                    final category = categories.firstWhere(
                      (c) => c.id == _category,
                    );
                    if (number < category.minimumPoints ||
                        number > category.maximumPoints) {
                      return l('Points must fit the category range.');
                    }
                  }
                  return null;
                },
              ),
              TextFormField(
                controller: _reason,
                enabled: !_busy && _pending == null && !_reconcile,
                maxLength: 2000,
                minLines: 2,
                maxLines: 5,
                decoration: InputDecoration(labelText: l('Reason for points')),
                validator: (value) => (value?.trim().isEmpty ?? true)
                    ? l('Enter a reason for the points.')
                    : null,
              ),
              Text(
                '${l('Daily staff point limit')}: ${_configuration!.settings.dailyStaffCap}',
              ),
              FilledButton(
                onPressed: _busy || _pending != null || _reconcile
                    ? null
                    : _issue,
                child: _busy
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : AcademicText('Award points'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
