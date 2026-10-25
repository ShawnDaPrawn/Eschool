import 'wellbeing_inbox_panel.dart';
import 'dart:convert';
import 'dart:math';
import 'dart:typed_data';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import 'reporting_repository.dart';
import 'private_report_image.dart';
import 'report_photo_picker.dart';

class IncidentReportingScreen extends StatefulWidget {
  const IncidentReportingScreen({
    required this.repository,
    required this.reporterName,
    this.learnerId,
    this.learnerName,
    this.openAnonymous,
    this.selectPhoto,
    super.key,
  });
  final ReportingRepository repository;
  final String reporterName;
  final int? learnerId;
  final String? learnerName;
  final Future<bool> Function(Uri)? openAnonymous;
  final Future<Uint8List?> Function()? selectPhoto;
  @override
  State<IncidentReportingScreen> createState() =>
      _IncidentReportingScreenState();
}

class _IncidentReportingScreenState extends State<IncidentReportingScreen>
    with WidgetsBindingObserver {
  ReportingRepository get repo => widget.repository;
  WellbeingAccess? _access;
  IncidentPublicConfiguration? _configuration;
  IncidentPublicView? _view;
  final _body = TextEditingController(),
      _code = TextEditingController(),
      _reply = TextEditingController();
  String _newSecret = '', _viewSecret = '', _urgency = 'routine';
  int? _category;
  bool _inbox = false;
  bool _busy = true,
      _hidden = true,
      _denied = false,
      _consent = false,
      _identifyConsent = false,
      _identifyAboutLearner = false,
      _aboutLearner = false;
  Uint8List? _selectedPhoto;
  bool _picking = false, _foreground = true;
  int _epoch = 0;
  String? _message, _pending, _action;
  bool get _active => mounted && repo.isActive;
  bool get _editable =>
      !_busy &&
      !_picking &&
      !_hidden &&
      !_denied &&
      _pending == null &&
      repo.isActive;
  bool get _write => _editable && _access?.canWrite == true;
  String l(String key) => academicText(context, key);
  static String secret() {
    final random = Random.secure();
    return List.generate(
      32,
      (_) => random.nextInt(256).toRadixString(16).padLeft(2, '0'),
    ).join();
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _bootstrap();
  }

  @override
  void dispose() {
    _epoch++;
    _clearPhoto();
    WidgetsBinding.instance.removeObserver(this);
    for (final c in [_body, _code, _reply]) {
      c.clear();
      c.dispose();
    }
    super.dispose();
  }

  void _clearPhoto() {
    _selectedPhoto?.fillRange(0, _selectedPhoto!.length, 0);
    _selectedPhoto = null;
  }

  void _erase() {
    _inbox = false;
    _clearPhoto();
    _view = null;
    _viewSecret = '';
    _newSecret = '';
    _pending = null;
    _action = null;
    _body.clear();
    _code.clear();
    _reply.clear();
    _consent = false;
    _identifyConsent = false;
    _identifyAboutLearner = false;
    _aboutLearner = false;
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    _foreground = state == AppLifecycleState.resumed;
    if (state != AppLifecycleState.resumed) {
      _epoch++;
      if (mounted)
        setState(() {
          _hidden = true;
          _busy = false;
          _erase();
        });
    } else {
      _bootstrap();
    }
  }

  bool _current(int epoch) => _active && epoch == _epoch;
  Future<void> _run(Future<void> Function(int) task) async {
    if (!_active) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _message = null;
    });
    try {
      await task(epoch);
    } catch (error) {
      if (!_current(epoch)) return;
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        _erase();
        _denied = true;
        _message = l(
          'Your reporting access changed. Reopen this area after refreshing your account.',
        );
      } else {
        _message = l(
          'The request could not be completed. Check the current record before retrying.',
        );
      }
    } finally {
      if (_current(epoch)) setState(() => _busy = false);
    }
  }

  Future<void> _bootstrap() async {
    if (!mounted) return;
    setState(() {
      _erase();
      _access = null;
      _configuration = null;
      _hidden = true;
      _denied = false;
    });
    await _run((epoch) async {
      final access = await repo.access();
      final configuration = await repo.configuration(access.schoolSlug);
      if (!_current(epoch)) return;
      _access = access;
      _configuration = configuration;
      _newSecret = secret();
      _category = null;
      _hidden = false;
    });
  }

  Future<void> _read({int? before}) async {
    if (!_editable) return;
    final code = before == null ? _code.text.trim() : _viewSecret;
    if (!RegExp(r'^[a-f0-9]{64}$').hasMatch(code)) {
      setState(() => _message = l('Enter the complete private report code.'));
      return;
    }
    await _run((epoch) async {
      final view = await repo.read(code, before: before);
      if (!_current(epoch)) return;
      _view = view;
      _viewSecret = code;
      _identifyConsent = false;
      _identifyAboutLearner = false;
      _reply.clear();
    });
  }

  Future<void> _save(String action, Map<String, dynamic> input) async {
    if (!_write) return;
    _pending = jsonEncode(input);
    _action = action;
    if (action == 'photo') _clearPhoto();
    await _retry();
  }

  Future<void> _retry() async {
    final pending = _pending, action = _action;
    if (pending == null || action == null || !_active || _busy) return;
    bool saved = false;
    await _run((epoch) async {
      final input = Map<String, dynamic>.from(jsonDecode(pending));
      try {
        IncidentPublicView? view;
        String code = input['secret'];
        switch (action) {
          case 'submit':
            final receipt = await repo.submit(input);
            if (!receipt.received)
              throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
            view = await repo.read(code);
            break;
          case 'reply':
            await repo.reply(code, input);
            view = await repo.read(code);
            break;
          case 'photo':
            await repo.uploadPhoto(
              code,
              base64Decode(input['bytes']),
              input['operation_id'],
            );
            view = await repo.read(code);
            break;
          case 'identify':
            view = await repo.identify(
              code,
              input['revision'],
              learner: input['learner_id'],
            );
            break;
          default:
            throw StateError('Invalid reporting action');
        }
        if (!_current(epoch)) return;
        _view = view;
        _viewSecret = code;
        _code.text = code;
        _pending = null;
        _action = null;
        _body.clear();
        _reply.clear();
        _consent = false;
        _newSecret = secret();
        saved = true;
      } on ApiException catch (error) {
        if (!_current(epoch)) return;
        if (error.statusCode == 422) {
          _pending = null;
          _action = null;
          _message = l(
            action == 'photo'
                ? 'The photo was not sent. Check its type and size, then choose it again.'
                : 'Check the entered values. Your unsent draft remains here.',
          );
        } else if (error.statusCode == 409) {
          _pending = null;
          _action = null;
          _message = l(
            'The report changed. Read its current state before trying a new action.',
          );
        } else {
          rethrow;
        }
      }
    });
    if (saved && _active)
      setState(
        () => _message = l(
          'Your report action was received. Keep the private code to read school replies.',
        ),
      );
  }

  Future<void> _choosePhoto() async {
    if (!_write) return;
    final generation = _epoch;
    setState(() => _picking = true);
    try {
      final bytes = await (widget.selectPhoto?.call() ?? selectReportPhoto());
      if (!_active || !_foreground || bytes == null) return;
      if (bytes.isEmpty || bytes.length > 4194304) {
        throw ApiException(
          'Choose a JPEG or PNG photo up to 4 MB.',
          isApiMessage: true,
        );
      }
      // An external picker may background the app: old bodies/codes are erased
      // immediately. Reopen fresh authority and require the code again before
      // an explicit send; never silently reattach to the former private report.
      if (generation != _epoch) {
        await _bootstrap();
      } else {
        await _run((epoch) async {
          final access = await repo.access();
          if (_current(epoch)) _access = access;
        });
      }
      if (!_active || !_foreground || _denied || _hidden) return;
      setState(() {
        _clearPhoto();
        _selectedPhoto = Uint8List.fromList(bytes);
        _message = l(
          'Photo selected. Read the intended report before sending it.',
        );
      });
    } catch (_) {
      if (_active && _foreground) {
        setState(() => _message = l('Choose a JPEG or PNG photo up to 4 MB.'));
      }
    } finally {
      if (mounted) setState(() => _picking = false);
    }
  }

  Future<void> _anonymous() async {
    if (!_editable || _access == null) return;
    final uri = repo.anonymousForm(_access!.schoolSlug);
    final opened =
        await (widget.openAnonymous?.call(uri) ??
            launchUrl(uri, mode: LaunchMode.externalApplication));
    if (_active && !opened)
      setState(
        () => _message = l(
          'The browser could not open. Try again or contact the school.',
        ),
      );
  }

  Widget _button(String text, VoidCallback? action) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: FilledButton(onPressed: action, child: Text(l(text))),
  );
  Widget _field(
    String text,
    TextEditingController c, {
    int lines = 1,
    int max = 20000,
    bool obscure = false,
    bool? enabled,
  }) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: TextField(
      controller: c,
      enabled: enabled ?? _write,
      maxLines: lines,
      maxLength: max,
      obscureText: obscure,
      autocorrect: false,
      enableSuggestions: false,
      decoration: InputDecoration(
        labelText: l(text),
        border: const OutlineInputBorder(),
      ),
    ),
  );
  Widget _heading(String text) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 12),
    child: Text(l(text), style: Theme.of(context).textTheme.titleLarge),
  );
  Future<void> _returnFromInbox() async {
    if (!_active || _busy) return;
    await _run((epoch) async {
      final access = await repo.access();
      final view = _viewSecret.isEmpty ? null : await repo.read(_viewSecret);
      if (!_current(epoch)) return;
      _access = access;
      if (view != null) _view = view;
      _inbox = false;
    });
  }

  void _inboxDenied() {
    if (!mounted) return;
    setState(() {
      _erase();
      _denied = true;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (!repo.isActive || _hidden || _denied)
      return ScreenScaffold(
        title: l('Report a concern'),
        body: Center(
          child: _busy
              ? const CircularProgressIndicator()
              : Text(
                  _message ??
                      l(
                        'Your reporting access changed. Reopen this area after refreshing your account.',
                      ),
                ),
        ),
      );
    final configuration = _configuration!;
    if (_inbox)
      return ScreenScaffold(
        title: l('Private updates'),
        body: WellbeingInboxPanel(
          repository: repo,
          onReturn: _returnFromInbox,
          onDenied: _inboxDenied,
        ),
      );
    return ScreenScaffold(
      title: l('Report a concern'),
      action: IconButton(
        tooltip: l('Private updates'),
        onPressed: _editable ? () => setState(() => _inbox = true) : null,
        icon: const Icon(Icons.notifications_outlined),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _heading('Immediate help'),
          SelectableText(configuration.emergencyGuidance),
          Text(l('This reporting area is not an emergency-response service.')),
          if (_busy) const LinearProgressIndicator(),
          if (_message != null)
            Semantics(liveRegion: true, child: Text(_message!)),
          if (_pending != null) ...[
            Text(
              l(
                'The result is unknown. Retry the same request without changing its contents.',
              ),
            ),
            _button('Retry unchanged request', _busy ? null : _retry),
          ],
          _heading('Report anonymously'),
          Text(
            l(
              'The anonymous form opens separately. Your signed-in identity is not attached to that report.',
            ),
          ),
          _button('Open anonymous reporting', _editable ? _anonymous : null),
          if (_access?.canWrite != true)
            Text(
              l(
                'Changes are unavailable. Existing reports can still be read with their private code.',
              ),
            ),
          _heading('Report with my identity'),
          Text('${l('Reporting as')}: ${widget.reporterName}'),
          DropdownButtonFormField<int>(
            initialValue: _category,
            decoration: InputDecoration(
              labelText: l('Concern category'),
              border: const OutlineInputBorder(),
            ),
            isExpanded: true,
            items: [
              for (final category in configuration.categories)
                DropdownMenuItem(
                  value: category.id,
                  child: Text(category.name, overflow: TextOverflow.ellipsis),
                ),
            ],
            onChanged: _write
                ? (value) => setState(() => _category = value)
                : null,
          ),
          if (_category != null)
            Text(
              configuration.categories
                  .firstWhere((row) => row.id == _category)
                  .emergencyGuidance,
            ),
          DropdownButtonFormField<String>(
            initialValue: _urgency,
            decoration: InputDecoration(labelText: l('Urgency')),
            items: [
              for (final value in ['routine', 'urgent'])
                DropdownMenuItem(value: value, child: Text(l(value))),
            ],
            onChanged: _write
                ? (value) => setState(() => _urgency = value!)
                : null,
          ),
          _field('What happened?', _body, lines: 6),
          if (widget.learnerId != null)
            CheckboxListTile(
              title: Text(
                '${l('This concern is about')}: ${widget.learnerName ?? ''}',
              ),
              value: _aboutLearner,
              onChanged: _write
                  ? (value) => setState(() => _aboutLearner = value!)
                  : null,
            ),
          CheckboxListTile(
            title: Text(
              l(
                'I agree to identify myself to the school safeguarding team for this report.',
              ),
            ),
            value: _consent,
            onChanged: _write
                ? (value) => setState(() => _consent = value!)
                : null,
          ),
          _button(
            'Send identified report',
            _write && _consent && _category != null
                ? () => _save('submit', {
                    'secret': _newSecret,
                    'category_id': _category,
                    'body': _body.text,
                    'urgency': _urgency,
                    'anonymous': false,
                    'identify_consent': true,
                    if (_aboutLearner && widget.learnerId != null)
                      'learner_id': widget.learnerId,
                  })
                : null,
          ),
          _heading('Read a report with my private code'),
          Text(
            l(
              'Keep the code somewhere private. The app does not store report codes or drafts. Identified reports also require the original signed-in reporter.',
            ),
          ),
          _field(
            'Private report code',
            _code,
            max: 64,
            obscure: true,
            enabled: _editable,
          ),
          _button('Read my report', _editable ? _read : null),
          if (_view != null) ...[
            _heading(
              _view!.anonymous ? 'Anonymous report' : 'Identified report',
            ),
            SelectableText(_view!.emergencyGuidance),
            SelectableText(_view!.body),
            Text('${l('Status')}: ${l(_view!.status.value)}'),
            if (_view!.photos?.isNotEmpty == true) _heading('Report photos'),
            for (final photo in _view!.photos ?? <IncidentPhoto>[])
              PrivateReportImage(
                key: ValueKey('report-photo-${photo.id}'),
                base64: photo.thumbnailBase64,
                label: l('Photo attached to this report'),
              ),
            if (!_view!.anonymous &&
                _configuration?.photoConstraints != null &&
                (_view!.photos?.length ?? 0) <
                    _configuration!.photoConstraints!.maximumPhotos) ...[
              Text(
                l(
                  'Photos may identify people through their contents. Device and location metadata is removed, but visible details remain.',
                ),
              ),
              _button('Choose report photo', _write ? _choosePhoto : null),
              if (_selectedPhoto != null)
                _button(
                  'Send selected photo',
                  _write
                      ? () => _save('photo', {
                          'secret': _viewSecret,
                          'bytes': base64Encode(_selectedPhoto!),
                          'operation_id': const Uuid().v4(),
                        })
                      : null,
                ),
            ],
            _heading('Keep your private code'),
            SelectableText(_viewSecret),
            for (final thread in _view!.threads)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(l(thread.author.value)),
                      SelectableText(thread.body),
                    ],
                  ),
                ),
              ),
            if (_view!.hasOlder)
              _button(
                'Read older replies',
                _editable ? () => _read(before: _view!.nextBefore) : null,
              ),
            _field('Add a follow-up', _reply, lines: 4),
            _button(
              'Send follow-up',
              _write
                  ? () => _save('reply', {
                      'secret': _viewSecret,
                      'body': _reply.text,
                      'operation_id': const Uuid().v4(),
                    })
                  : null,
            ),
            if (_view!.anonymous) ...[
              Text(
                l(
                  'Identifying this report is permanent. The code alone will no longer open it; your signed-in identity will also be required.',
                ),
              ),
              if (widget.learnerId != null)
                CheckboxListTile(
                  title: Text(
                    '${l('Identify this concern as being about')}: ${widget.learnerName ?? ''}',
                  ),
                  value: _identifyAboutLearner,
                  onChanged: _write
                      ? (value) =>
                            setState(() => _identifyAboutLearner = value!)
                      : null,
                ),
              CheckboxListTile(
                title: Text(
                  l('I agree to identify myself for this existing report.'),
                ),
                value: _identifyConsent,
                onChanged: _write
                    ? (value) => setState(() => _identifyConsent = value!)
                    : null,
              ),
              _button(
                'Identify this report',
                _write && _identifyConsent
                    ? () => _save('identify', {
                        'secret': _viewSecret,
                        'revision': _view!.revision,
                        if (_identifyAboutLearner && widget.learnerId != null)
                          'learner_id': widget.learnerId,
                      })
                    : null,
              ),
            ],
          ],
        ],
      ),
    );
  }
}
