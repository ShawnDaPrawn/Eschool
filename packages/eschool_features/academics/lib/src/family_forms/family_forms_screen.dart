import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import '../wellbeing/school_wall_time.dart';
import 'family_form_repository.dart';
import 'family_health_screen.dart';

class FamilyFormsScreen extends StatefulWidget {
  const FamilyFormsScreen({
    required this.repository,
    this.staffOnly = false,
    this.pickHealthFile = pickProfileFile,
    this.monotonicNow,
    super.key,
  });
  final FamilyFormRepository repository;
  final bool staffOnly;
  final ProfileFilePicker pickHealthFile;
  final Duration Function()? monotonicNow;
  @override
  State<FamilyFormsScreen> createState() => _FamilyFormsScreenState();
}

class _FamilyFormsScreenState extends State<FamilyFormsScreen>
    with WidgetsBindingObserver {
  FamilyFormConfiguration? _configuration;
  final List<FamilyConsentSummary> _items = [];
  FamilyConsentDetail? _detail;
  FamilyConsentManaged? _managed;
  int? _learner, _group, _nextPage;
  int _generation = 0;
  bool _foreground = true,
      _loading = false,
      _busy = false,
      _acknowledged = false,
      _picking = false;
  String? _error;
  final Stopwatch _clock = Stopwatch()..start();
  Duration _now() => widget.monotonicNow?.call() ?? _clock.elapsed;
  String _decision = 'no';
  final _name = TextEditingController();
  (String, int, FamilyConsentSign)? _pendingSign;
  (String, String, int)? _pendingCancel;
  GlobalKey<NavigatorState> _navigator = GlobalKey();
  bool get _pending => _pendingSign != null || _pendingCancel != null;
  bool _active(int generation) =>
      mounted &&
      _foreground &&
      generation == _generation &&
      widget.repository.isActive;
  String l(String text) => academicText(context, text);
  void _erase() {
    _configuration = null;
    _items.clear();
    _detail = null;
    _managed = null;
    _nextPage = null;
    _error = null;
    _pendingSign = null;
    _pendingCancel = null;
    _name.clear();
    _acknowledged = false;
    _busy = false;
    _loading = false;
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void didUpdateWidget(FamilyFormsScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository ||
        oldWidget.staffOnly != widget.staffOnly) {
      _generation++;
      _erase();
      _navigator = GlobalKey();
      _load();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _generation++;
      setState(() {
        _foreground = false;
        _erase();
        _navigator = GlobalKey();
      });
    } else {
      _foreground = true;
      if (!_picking) _load();
    }
  }

  @override
  void dispose() {
    _generation++;
    WidgetsBinding.instance.removeObserver(this);
    _name.clear();
    _name.dispose();
    super.dispose();
  }

  String _message(Object error) => error is ApiException && error.isApiMessage
      ? error.errorMessage
      : 'The family form could not be loaded. Try again.';
  void _fail(Object error, int generation) {
    if (!mounted || !_foreground || generation != _generation) return;
    setState(() {
      if (!widget.repository.isActive ||
          (error is ApiException &&
              [401, 403, 404].contains(error.statusCode))) {
        _erase();
      }
      _loading = false;
      _busy = false;
      _error = _message(error);
    });
  }

  Future<void> _load() async {
    if (_pending || _picking || !_foreground) return;
    final generation = ++_generation;
    setState(() {
      _erase();
      _loading = true;
    });
    try {
      final configuration = await widget.repository.configuration();
      if (!_active(generation)) return;
      // Validate the school zone before rendering dates; never guess the device zone.
      SchoolWallTime.local(DateTime.now().toUtc(), configuration.timezone);
      final options = widget.staffOnly
          ? configuration.groups.map((g) => g.id).toList()
          : configuration.children.map((c) => c.id).toList();
      var selected = widget.staffOnly ? _group : _learner;
      if (!options.contains(selected)) selected = options.firstOrNull;
      final page = selected == null
          ? null
          : await widget.repository.listing(
              learnerId: widget.staffOnly ? null : selected,
              groupId: widget.staffOnly ? selected : null,
            );
      if (!_active(generation)) return;
      setState(() {
        _configuration = configuration;
        if (widget.staffOnly) {
          _group = selected;
          _learner = null;
        } else {
          _learner = selected;
          _group = null;
        }
        _items.addAll(page?.items ?? []);
        _nextPage = page?.nextPage;
        _loading = false;
      });
    } catch (error) {
      _fail(error, generation);
    }
  }

  Future<void> _more() async {
    if (_busy || _loading || _nextPage == null || _pending) return;
    final generation = _generation;
    setState(() => _busy = true);
    try {
      final page = await widget.repository.listing(
        learnerId: _learner,
        groupId: _group,
        page: _nextPage!,
      );
      if (!_active(generation)) return;
      setState(() {
        for (final item in page.items) {
          if (!_items.any((old) => old.id == item.id)) _items.add(item);
        }
        _nextPage = page.nextPage;
        _busy = false;
      });
    } catch (error) {
      _fail(error, generation);
    }
  }

  Future<void> _open(String id) async {
    if (_pending || _busy || _loading) return;
    final generation = _generation;
    setState(() {
      _detail = null;
      _managed = null;
      _error = null;
      _name.clear();
      _acknowledged = false;
      _loading = true;
    });
    try {
      if (widget.staffOnly) {
        final result = await widget.repository.managed(id, _group!);
        if (!_active(generation)) return;
        setState(() => _managed = result);
      } else {
        final result = await widget.repository.read(id, _learner!);
        if (!_active(generation)) return;
        setState(() {
          _detail = result;
          _decision = result.decision ?? 'no';
        });
      }
      if (_active(generation)) setState(() => _loading = false);
    } catch (error) {
      _fail(error, generation);
    }
  }

  Future<void> _rosterPage() async {
    final record = _managed;
    if (record == null || record.nextPage == null || _busy || _pending) return;
    final generation = _generation;
    setState(() => _busy = true);
    try {
      final next = await widget.repository.managed(
        record.id,
        _group!,
        page: record.nextPage!,
      );
      if (!_active(generation)) return;
      if (record.fingerprint != next.fingerprint) {
        throw ApiException(
          'The consent wording changed. Reopen this request.',
          statusCode: 409,
          isApiMessage: true,
        );
      }
      final rows = [...record.recipients];
      for (final row in next.recipients) {
        if (!rows.any((old) => old.learnerId == row.learnerId)) rows.add(row);
      }
      setState(() {
        _managed = FamilyConsentManaged(
          id: next.id,
          title: next.title,
          activityGroupId: next.activityGroupId,
          sessionYearId: next.sessionYearId,
          status: next.status,
          eventStartsAt: next.eventStartsAt,
          eventEndsAt: next.eventEndsAt,
          respondBy: next.respondBy,
          wording: next.wording,
          fingerprint: next.fingerprint,
          revision: next.revision,
          recipients: rows,
          nextPage: next.nextPage,
        );
        _busy = false;
      });
    } catch (error) {
      _fail(error, generation);
    }
  }

  Future<void> _sign() async {
    final record = _detail;
    if (record == null || _busy || _learner == null) return;
    if (_pendingSign == null) {
      if (!_acknowledged || _name.text.trim().isEmpty) {
        setState(
          () => _error =
              'Read the complete wording, type your account name and confirm your decision.',
        );
        return;
      }
      _pendingSign = (
        record.id,
        _learner!,
        FamilyConsentSign(
          operationId: const Uuid().v4(),
          revision: record.revision,
          fingerprint: record.fingerprint,
          decision: _decision,
          acknowledged: true,
          signedName: _name.text.trim(),
        ),
      );
    }
    final intent = _pendingSign!;
    final generation = _generation;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.repository.sign(intent.$1, intent.$2, intent.$3);
      if (!_active(generation)) return;
      setState(() {
        _pendingSign = null;
        _name.clear();
        _acknowledged = false;
        _busy = false;
      });
      await _open(intent.$1);
    } catch (error) {
      if (!mounted || !_foreground || generation != _generation) return;
      if (error is ApiException &&
          error.statusCode != null &&
          error.statusCode! < 500 &&
          !error.isTransportFailure) {
        _pendingSign = null;
      }
      _fail(error, generation);
    }
  }

  Future<void> _cancel() async {
    final record = _managed;
    if (record == null || _busy) return;
    _pendingCancel ??= (record.id, const Uuid().v4(), record.revision);
    final intent = _pendingCancel!, generation = _generation;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.repository.cancel(intent.$1, intent.$2, intent.$3);
      if (!_active(generation)) return;
      setState(() {
        _pendingCancel = null;
        _busy = false;
      });
      await _open(intent.$1);
    } catch (error) {
      if (!mounted || !_foreground || generation != _generation) return;
      if (error is ApiException &&
          error.statusCode != null &&
          error.statusCode! < 500 &&
          !error.isTransportFailure) {
        _pendingCancel = null;
      }
      _fail(error, generation);
    }
  }

  String _date(DateTime time) => SchoolWallTime.local(
    time,
    _configuration!.timezone,
  ).replaceFirst('T', ' ');
  Widget _dates(DateTime starts, DateTime ends, DateTime deadline) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text('${l('School timezone')}: ${_configuration!.timezone}'),
      Text('${l('Activity starts')}: ${_date(starts)}'),
      Text('${l('Activity ends')}: ${_date(ends)}'),
      Text('${l('Positive response deadline')}: ${_date(deadline)}'),
    ],
  );
  Widget _record() {
    final record = _detail!;
    final writable = _configuration!.writable;
    final canYes =
        writable && record.respondBy.toUtc().isAfter(DateTime.now().toUtc());
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(record.title, style: Theme.of(context).textTheme.headlineSmall),
        _dates(record.eventStartsAt, record.eventEndsAt, record.respondBy),
        const SizedBox(height: 16),
        Text(
          l('Exact consent wording'),
          style: Theme.of(context).textTheme.titleMedium,
        ),
        SelectableText(record.wording),
        const SizedBox(height: 16),
        Text(
          '${l('Last recorded decision')}: ${l(record.decision == null
              ? 'No response'
              : record.decision == 'yes'
              ? 'Yes'
              : 'No')}',
        ),
        if (record.status == 'open') ...[
          if (!canYes) Text(l('You can still withdraw permission.')),
          if (!_pending) ...[
            DropdownButtonFormField<String>(
              initialValue: canYes ? _decision : 'no',
              isExpanded: true,
              decoration: InputDecoration(labelText: l('Your decision')),
              items: [
                if (canYes)
                  DropdownMenuItem(value: 'yes', child: Text(l('Yes'))),
                DropdownMenuItem(value: 'no', child: Text(l('No'))),
              ],
              onChanged: _busy
                  ? null
                  : (value) => setState(() => _decision = value ?? 'no'),
            ),
            Text(
              '${l('Name on your guardian account')}: ${_configuration!.signatureName}',
            ),
            TextField(
              controller: _name,
              enabled: !_busy,
              maxLength: 191,
              decoration: InputDecoration(
                labelText: l('Type your account name'),
              ),
            ),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              value: _acknowledged,
              onChanged: _busy
                  ? null
                  : (value) => setState(() => _acknowledged = value == true),
              title: Text(
                l(
                  'I have read this exact request and confirm my decision for this learner.',
                ),
              ),
            ),
            FilledButton(
              onPressed: _busy
                  ? null
                  : () {
                      if (!canYes) _decision = 'no';
                      _sign();
                    },
              child: Text(l('Record my signed decision')),
            ),
          ],
        ],
      ],
    );
  }

  Widget _staffRecord() {
    final record = _managed!;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(record.title, style: Theme.of(context).textTheme.headlineSmall),
        _dates(record.eventStartsAt, record.eventEndsAt, record.respondBy),
        const SizedBox(height: 16),
        Text(
          l('Exact consent wording'),
          style: Theme.of(context).textTheme.titleMedium,
        ),
        SelectableText(record.wording),
        const SizedBox(height: 16),
        Text(
          l('Current participant permissions'),
          style: Theme.of(context).textTheme.titleMedium,
        ),
        for (final row in record.recipients)
          Card(
            child: ListTile(
              title: Text(row.name),
              subtitle: Text(
                '${l(row.effective ? 'Current signed permission' : 'Permission is not current')}\n${l('Last recorded decision')}: ${l(row.decision == null
                    ? 'No response'
                    : row.decision == 'yes'
                    ? 'Yes'
                    : 'No')}',
              ),
            ),
          ),
        if (record.nextPage != null)
          OutlinedButton(
            onPressed: _busy || _pending ? null : _rosterPage,
            child: Text(l('More participants')),
          ),
        if (record.status == 'open' && !_pending)
          OutlinedButton(
            onPressed: _busy ? null : _cancel,
            child: Text(l('Cancel consent request')),
          ),
      ],
    );
  }

  void _openHealth(FamilyFormChoice child) {
    _navigator.currentState?.push(
      MaterialPageRoute<void>(
        builder: (_) => FamilyHealthScreen(
          repository: widget.repository,
          child: child,
          selectFile: (subject, field) {
            _chooseHealthDocument(child, subject, field);
          },
        ),
      ),
    );
  }

  Future<void> _chooseHealthDocument(
    FamilyFormChoice child,
    ProfileSubject subject,
    String field,
  ) async {
    if (_picking ||
        _busy ||
        widget.staffOnly ||
        subject.schoolId != widget.repository.schoolId ||
        subject.personId != child.personId ||
        subject.profileType != 'learner') {
      return;
    }
    final repository = widget.repository,
        deadline = _now() + const Duration(seconds: 60);
    bool sameReader() =>
        mounted &&
        identical(repository, widget.repository) &&
        repository.isActive;
    ProfileUpload? selected;
    var reopen = false;
    String? failure;
    _generation++;
    setState(() {
      _erase();
      _picking = true;
      _navigator = GlobalKey();
    });
    try {
      // Destroy the old editor, its drafts and attachment routes before opening
      // an OS picker. Only child/field/scope metadata survives this handoff.
      await WidgetsBinding.instance.endOfFrame;
      if (!sameReader()) return;
      selected = await widget.pickHealthFile();
      for (
        var round = 0;
        round < 125 &&
            sameReader() &&
            WidgetsBinding.instance.lifecycleState != null &&
            WidgetsBinding.instance.lifecycleState != AppLifecycleState.resumed;
        round++
      ) {
        await Future<void>.delayed(const Duration(milliseconds: 16));
      }
      if (!sameReader() || !_foreground) return;
      if (_now() > deadline) {
        throw ApiException(
          'The document selection expired. Choose it again.',
          isApiMessage: true,
        );
      }
      final access = await repository.configuration();
      if (!sameReader() || !_foreground) return;
      if (_now() > deadline) {
        throw ApiException(
          'The document selection expired. Choose it again.',
          isApiMessage: true,
        );
      }
      if (!access.writable ||
          !access.children.any(
            (current) =>
                current.id == child.id && current.personId == child.personId,
          )) {
        throw ApiException(
          'Your health access changed. Reopen the form.',
          statusCode: 403,
          isApiMessage: true,
        );
      }
      final form = await repository.health(child.id, child.personId);
      final allowed = form.categories
          .expand((category) => category.fields)
          .any(
            (definition) =>
                definition.key == field &&
                definition.type == 'file' &&
                definition.editable,
          );
      if (!sameReader() || !_foreground) return;
      if (_now() > deadline) {
        throw ApiException(
          'The document selection expired. Choose it again.',
          isApiMessage: true,
        );
      }
      if (!allowed) {
        throw ApiException(
          'Your health access changed. Reopen the form.',
          statusCode: 403,
          isApiMessage: true,
        );
      }
      if (selected != null) {
        await repository.uploadHealth(
          child.id,
          child.personId,
          field,
          selected.name,
          selected.bytes,
        );
      }
      reopen = true;
    } catch (error) {
      failure = error is ApiException && error.isTransportFailure
          ? 'The upload could not be confirmed. Refresh the health form before choosing another document.'
          : _message(error);
    } finally {
      selected?.bytes.fillRange(0, selected.bytes.length, 0);
      if (mounted) setState(() => _picking = false);
    }
    if (!sameReader() || !_foreground) return;
    await _load();
    if (failure != null && mounted) setState(() => _error = failure);
    if (reopen) await WidgetsBinding.instance.endOfFrame;
    if (reopen &&
        _foreground &&
        sameReader() &&
        _configuration?.children.any(
              (current) =>
                  current.id == child.id && current.personId == child.personId,
            ) ==
            true) {
      _openHealth(
        _configuration!.children.firstWhere(
          (current) => current.id == child.id,
        ),
      );
    }
  }

  Widget _body(BuildContext innerContext) => ScreenScaffold(
    title: l(
      widget.staffOnly ? 'Activity consent' : 'Family consent and health',
    ),
    onBack: () => Navigator.of(context, rootNavigator: true).maybePop(),
    action: IconButton(
      tooltip: l('Refresh'),
      onPressed: _busy || _pending || _picking ? null : _load,
      icon: const Icon(Icons.refresh),
    ),
    body: ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_picking) Text(l('Finish choosing the private document.')),
        if (_error != null)
          Semantics(liveRegion: true, child: Text(l(_error!))),
        if (_pending) ...[
          Text(
            l(
              'This change may have been recorded. Retry the same change before making another decision.',
            ),
          ),
          FilledButton(
            onPressed: _busy ? null : (_pendingSign != null ? _sign : _cancel),
            child: Text(l('Retry the same change')),
          ),
        ],
        if (_loading || _busy) const LinearProgressIndicator(),
        if (_configuration != null) ...[
          if (_detail != null)
            _record()
          else if (_managed != null)
            _staffRecord()
          else ...[
            if (widget.staffOnly && _configuration!.groups.isNotEmpty)
              DropdownButtonFormField<int>(
                initialValue: _group,
                isExpanded: true,
                decoration: InputDecoration(labelText: l('Managed activity')),
                items: [
                  for (final group in _configuration!.groups)
                    DropdownMenuItem(
                      value: group.id,
                      child: Text(group.name, overflow: TextOverflow.ellipsis),
                    ),
                ],
                onChanged: _busy || _pending
                    ? null
                    : (id) {
                        _group = id;
                        _load();
                      },
              ),
            if (!widget.staffOnly && _configuration!.children.isNotEmpty) ...[
              DropdownButtonFormField<int>(
                initialValue: _learner,
                isExpanded: true,
                decoration: InputDecoration(labelText: l('Your learner')),
                items: [
                  for (final child in _configuration!.children)
                    DropdownMenuItem(
                      value: child.id,
                      child: Text(child.name, overflow: TextOverflow.ellipsis),
                    ),
                ],
                onChanged: _busy || _pending
                    ? null
                    : (id) {
                        _learner = id;
                        _load();
                      },
              ),
              OutlinedButton.icon(
                icon: const Icon(Icons.health_and_safety_outlined),
                label: Text(l('Health forms')),
                onPressed: _learner == null || _busy
                    ? null
                    : () {
                        final child = _configuration!.children.firstWhere(
                          (child) => child.id == _learner,
                        );
                        _openHealth(child);
                      },
              ),
            ],
            if (_items.isEmpty && !_loading)
              Text(l('No consent requests are available in this scope.')),
            for (final item in _items)
              Card(
                child: ListTile(
                  title: Text(item.title),
                  subtitle: Text(
                    '${l('Activity starts')}: ${_date(item.eventStartsAt)}',
                  ),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: _busy || _pending ? null : () => _open(item.id),
                ),
              ),
            if (_nextPage != null)
              TextButton(
                onPressed: _busy ? null : _more,
                child: Text(l('Older requests')),
              ),
          ],
          if ((_detail != null || _managed != null) && !_pending)
            TextButton(
              onPressed: _busy
                  ? null
                  : () => setState(() {
                      _detail = null;
                      _managed = null;
                      _name.clear();
                      _acknowledged = false;
                      _error = null;
                    }),
              child: Text(l('Back to consent requests')),
            ),
        ],
        if (_configuration == null && !_loading)
          TextButton(
            onPressed: _picking ? null : _load,
            child: Text(l('Refresh')),
          ),
      ],
    ),
  );
  @override
  Widget build(BuildContext context) {
    if (!_foreground || !widget.repository.isActive) {
      return ScreenScaffold(
        title: l(
          widget.staffOnly ? 'Activity consent' : 'Family consent and health',
        ),
        body: const Center(
          child: AcademicText(
            'Your family access changed. Reopen this screen.',
          ),
        ),
      );
    }
    return NavigatorPopHandler<void>(
      onPopWithResult: (_) => _navigator.currentState?.maybePop(),
      child: Navigator(
        key: _navigator,
        onGenerateRoute: (_) => MaterialPageRoute<void>(builder: _body),
      ),
    );
  }
}
