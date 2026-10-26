import 'dart:async';
import 'dart:convert';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import '../content/content_widgets.dart';
import '../private/private_pending_intent.dart';
import 'assistance_repository.dart';

/// Parent and explicitly authorised office work share rendering, not authority.
class SchoolAssistanceScreen extends StatefulWidget {
  const SchoolAssistanceScreen({
    required this.repository,
    this.learnerId,
    this.learnerName,
    this.office = false,
    super.key,
  });
  final AssistanceRepository repository;
  final int? learnerId;
  final String? learnerName;
  final bool office;
  @override
  State<SchoolAssistanceScreen> createState() => _SchoolAssistanceScreenState();
}

class _SchoolAssistanceScreenState extends State<SchoolAssistanceScreen>
    with WidgetsBindingObserver {
  SchoolAssistanceConfiguration? _config;
  SchoolAssistanceDocumentPage? _documents;
  SchoolAssistanceThreadPage? _threads;
  SchoolAssistanceOfficeQueue? _queue;
  SchoolAssistanceThread? _thread;
  SchoolAssistanceDocumentBody? _document;
  SchoolAssistanceTurn? _turn;
  SchoolAssistanceStaffChoices? _staff;
  final _question = TextEditingController(),
      _reply = TextEditingController(),
      _title = TextEditingController(),
      _markdown = TextEditingController(),
      _reference = TextEditingController(),
      _reason = TextEditingController(),
      _expiry = TextEditingController();
  final _selected = <String>{};
  bool _calendar = true,
      _records = true,
      _reviewed = false,
      _documentReviewed = false,
      _foreground = true,
      _busy = false,
      _loading = false,
      _retired = false;
  int _generation = 0, _docPage = 1, _threadPage = 1;
  int? _assignee;
  String _language = 'en';
  String? _error, _notice;
  Timer? _turnExpiry, _pendingExpiry, _privateReadExpiry;
  bool _expiredChecked = false;
  PrivatePendingIntent? _pending;
  AssistanceWrite? _pendingAction;
  String? _pendingReference;
  int? _pendingLearner;
  String l(String key) => academicText(context, key);
  bool _active(int generation) =>
      mounted &&
      !_retired &&
      _foreground &&
      generation == _generation &&
      widget.repository.isActive;
  bool get _canRead => !_busy && !_loading && _config != null;
  bool get _idle => !_busy && !_loading && _config != null && _pending == null;
  bool get _canWrite => _idle && _config?.writable == true;
  String get _persona => widget.office ? 'office' : 'parent';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void didUpdateWidget(SchoolAssistanceScreen old) {
    super.didUpdateWidget(old);
    if (old.repository != widget.repository ||
        old.learnerId != widget.learnerId ||
        old.office != widget.office) {
      _generation++;
      _pending?.close();
      _pending = null;
      _retired = false;
      _erase();
      _load();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      _generation++;
      _foreground = false;
      if (mounted) setState(_erase);
    } else {
      if (mounted) setState(() => _foreground = true);
      _load();
    }
  }

  void _erase() {
    _config = null;
    _documents = null;
    _threads = null;
    _queue = null;
    _thread = null;
    _document = null;
    _turn = null;
    _staff = null;
    _selected.clear();
    _assignee = null;
    _reviewed = false;
    _documentReviewed = false;
    _turnExpiry?.cancel();
    _privateReadExpiry?.cancel();
    for (final controller in [
      _question,
      _reply,
      _title,
      _markdown,
      _reference,
      _reason,
      _expiry,
    ]) {
      controller.clear();
    }
    _error = null;
    _notice = null;
    _busy = false;
    _loading = false;
  }

  @override
  void dispose() {
    _generation++;
    WidgetsBinding.instance.removeObserver(this);
    _turnExpiry?.cancel();
    _pending?.close();
    _pendingExpiry?.cancel();
    _privateReadExpiry?.cancel();
    for (final controller in [
      _question,
      _reply,
      _title,
      _markdown,
      _reference,
      _reason,
      _expiry,
    ]) {
      controller.clear();
      controller.dispose();
    }
    super.dispose();
  }

  String _failure(Object error) {
    if (error is ApiException && error.statusCode == 429) {
      return 'The current assistance allowance has been reached. You can still read school documents or contact the office.';
    }
    if (error is ApiException && error.statusCode == 503) {
      return 'A reply was not confirmed. School answers may be unavailable. Check or retry this exact request; do not send a replacement.';
    }
    if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
      return 'This school information is no longer available to this account.';
    }
    if (error is ApiException && error.statusCode == 409) {
      return 'The saved information changed. Refresh it before making another change.';
    }
    if (error is ApiException && error.isApiMessage) return error.errorMessage;
    return 'The result was not confirmed. Retry the same request after checking your access.';
  }

  bool _accept(int generation) {
    if (_active(generation)) return true;
    if (mounted &&
        !_retired &&
        _foreground &&
        generation == _generation &&
        !widget.repository.isActive) {
      setState(() {
        _erase();
        _pending?.close();
        _pending = null;
        _retired = true;
        _error = 'Your school access changed. Reopen this screen.';
      });
    }
    return false;
  }

  Future<void> _load() async {
    if (_retired) return;
    final generation = ++_generation;
    if (mounted) {
      setState(() {
        if (widget.office && _document != null) {
          for (final controller in [_title, _markdown, _reference, _expiry]) {
            controller.clear();
          }
        }
        _documentReviewed = false;
        _config = null;
        _documents = null;
        _threads = null;
        _queue = null;
        _thread = null;
        _document = null;
        _privateReadExpiry?.cancel();
        _turn = null;
        _turnExpiry?.cancel();
        _reviewed = false;
        _loading = true;
        _error = null;
      });
    }
    try {
      final config = await widget.repository.configuration();
      if (!_accept(generation)) return;
      if (widget.office
          ? !config.canOffice
          : !config.canParent || (widget.learnerId ?? 0) < 1) {
        throw ApiException(
          'Select an authorised school profile and child.',
          statusCode: 403,
          isApiMessage: true,
        );
      }
      final docs = await widget.repository.documents(
        widget.learnerId ?? 0,
        page: _docPage,
        office: widget.office,
      );
      final threads = widget.office
          ? null
          : await widget.repository.threads(
              widget.learnerId!,
              page: _threadPage,
            );
      final queue = widget.office
          ? await widget.repository.queue(page: _threadPage)
          : null;
      if (!_accept(generation)) return;
      setState(() {
        _config = config;
        _documents = docs;
        _threads = threads;
        _queue = queue;
        if (!config.languages.contains(_language)) {
          _language = config.languages.first;
        }
        _expiredChecked = _pending?.expired == true;
      });
    } catch (error) {
      if (_accept(generation)) {
        setState(() {
          _erase();
          if (error is ApiException && [401, 403].contains(error.statusCode)) {
            _retired = true;
            _pending?.close();
            _pending = null;
          }
          _error = _failure(error);
        });
      }
    } finally {
      if (_active(generation)) setState(() => _loading = false);
    }
  }

  void _readFailure(Object error) {
    final status = error is ApiException ? error.statusCode : null;
    if ([401, 403, 404].contains(status)) _erase();
    if ([401, 403].contains(status)) {
      _retired = true;
      _pending?.close();
      _pending = null;
    }
    _error = _failure(error);
  }

  Future<void> _readThread(String id, {int page = 1}) async {
    final previous = _thread?.id;
    final generation = _generation;
    setState(() {
      _thread = null;
      _turn = null;
      _reviewed = false;
      if (previous != id) _reply.clear();
      _busy = true;
      _error = null;
    });
    try {
      final thread = await widget.repository.thread(
        id,
        office: widget.office,
        page: page,
      );
      if (!widget.office && thread.learnerId != widget.learnerId) {
        throw const FormatException('The selected child changed.');
      }
      final staff = widget.office ? await widget.repository.staff() : null;
      if (_accept(generation)) {
        setState(() {
          _thread = thread;
          _staff = staff;
          _leasePrivateRead(thread.expiresAt);
        });
      }
    } catch (error) {
      if (_accept(generation)) setState(() => _readFailure(error));
    } finally {
      if (_active(generation)) setState(() => _busy = false);
    }
  }

  Future<void> _readDocument(String id) async {
    final generation = _generation;
    setState(() {
      _document = null;
      _busy = true;
      _error = null;
    });
    try {
      final doc = await widget.repository.document(
        id,
        learner: widget.office ? null : widget.learnerId,
      );
      if (_accept(generation)) {
        setState(() {
          _document = doc;
          _leasePrivateRead();
          if (widget.office) {
            _title.text = doc.title;
            _markdown.text = doc.markdown;
            _reference.text = doc.sourceReference;
            _expiry.text = doc.expiresOn ?? '';
            _documentReviewed = false;
          }
        });
      }
    } catch (error) {
      if (_accept(generation)) setState(() => _readFailure(error));
    } finally {
      if (_active(generation)) setState(() => _busy = false);
    }
  }

  void _leasePrivateRead([DateTime? expiresAt]) {
    _privateReadExpiry?.cancel();
    var lease = const Duration(minutes: 15);
    if (expiresAt != null) {
      final left = expiresAt.toUtc().difference(DateTime.now().toUtc());
      if (left < lease) lease = left;
    }
    _privateReadExpiry = Timer(lease.isNegative ? Duration.zero : lease, () {
      if (mounted) {
        setState(() {
          _generation++;
          _erase();
          _error =
              'The private view expired. Check current access to read it again.';
        });
      }
    });
  }

  void _showTurn(SchoolAssistanceTurn turn) {
    if ((!widget.office && turn.learnerId != widget.learnerId) ||
        !Uuid.isValidUUID(fromString: turn.id)) {
      throw const FormatException('The reviewed child or source changed.');
    }
    if (turn.response != null &&
        (turn.response!.language != turn.language ||
            turn.response!.citations.any(
              (token) => !turn.citations.any((source) => source.token == token),
            ))) {
      throw const FormatException('Invalid answer sources.');
    }
    _turnExpiry?.cancel();
    final left = turn.expiresAt.toUtc().difference(DateTime.now().toUtc());
    if (left <= Duration.zero) {
      throw const FormatException('The reviewed text expired.');
    }
    _turnExpiry = Timer(
      left > const Duration(minutes: 15) ? const Duration(minutes: 15) : left,
      () {
        if (mounted) {
          setState(() {
            _turn = null;
            _reviewed = false;
            _notice =
                'The reviewed text expired. Check current school sources before starting again.';
          });
        }
      },
    );
    _turn = turn;
    _reviewed = false;
  }

  Future<void> _submit(
    AssistanceWrite action,
    Map<String, dynamic> body, {
    String? reference,
    int? learner,
  }) async {
    if (!_canWrite) return;
    _pending?.close();
    _pendingAction = action;
    _pendingReference = reference;
    _pendingLearner = learner;
    _pending = PrivatePendingIntent(
      school: widget.repository.schoolId,
      reader: widget.repository.readerId,
      body: body,
    );
    _expiredChecked = false;
    _pendingExpiry?.cancel();
    final retained = _pending;
    _pendingExpiry = Timer(const Duration(minutes: 15), () {
      if (mounted && identical(_pending, retained)) {
        setState(() {
          retained?.close();
          _expiredChecked = false;
        });
      }
    });
    await _sendPending();
  }

  Future<void> _sendPending() async {
    if (_pending == null ||
        _pendingAction == null ||
        _busy ||
        _config == null ||
        _config!.writable != true) {
      return;
    }
    final generation = _generation, action = _pendingAction!;
    final reference = _pendingReference, learner = _pendingLearner;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      // Re-authorise exact source before decrypting a retained submitted body.
      if ([
        AssistanceWrite.reply,
        AssistanceWrite.close,
        AssistanceWrite.reassign,
      ].contains(action)) {
        await widget.repository.thread(reference!, office: widget.office);
      } else if (action == AssistanceWrite.run) {
        await widget.repository.turn(reference!);
      } else if (!widget.office && learner != null) {
        await widget.repository.documents(learner);
      } else {
        await widget.repository.configuration();
      }
      if (!_accept(generation)) return;
      final body = _pending!.open(
        school: widget.repository.schoolId,
        reader: widget.repository.readerId,
        authorized: true,
      );
      final result = await widget.repository.write(
        action,
        body,
        learner: learner,
        reference: reference,
      );
      if (!_accept(generation)) return;
      if ([
        AssistanceWrite.answer,
        AssistanceWrite.translation,
        AssistanceWrite.officeTranslation,
        AssistanceWrite.run,
      ].contains(action)) {
        final turn = SchoolAssistanceTurn.fromJson(result);
        if ((action == AssistanceWrite.run && turn.id != reference) ||
            (body['language'] != null && turn.language != body['language']) ||
            (action == AssistanceWrite.answer &&
                turn.purpose.value != 'answer') ||
            ([
                  AssistanceWrite.translation,
                  AssistanceWrite.officeTranslation,
                ].contains(action) &&
                turn.purpose.value != 'translation')) {
          throw const FormatException('The reviewed source changed.');
        }
        _showTurn(turn);
      } else if (result['id'] is! String ||
          !Uuid.isValidUUID(fromString: result['id'] as String) ||
          (reference != null && result['id'] != reference)) {
        throw const FormatException(
          'The saved school-support reference changed.',
        );
      }
      _pending!.close();
      _pendingExpiry?.cancel();
      _pending = null;
      setState(() {
        _notice = 'Saved with the school.';
        if (action == AssistanceWrite.open) _question.clear();
        _reply.clear();
        _reason.clear();
        _busy = false;
      });
      if ([
        AssistanceWrite.open,
        AssistanceWrite.reply,
        AssistanceWrite.close,
        AssistanceWrite.claim,
      ].contains(action)) {
        await _readThread(result['id'] as String);
      } else if (action == AssistanceWrite.reassign) {
        setState(() {
          _thread = null;
          _staff = null;
          _assignee = null;
          _turn = null;
          _reviewed = false;
          _turnExpiry?.cancel();
        });
      } else if ([
        AssistanceWrite.publish,
        AssistanceWrite.withdraw,
      ].contains(action)) {
        await _readDocument(result['id'] as String);
      }
    } catch (error) {
      if (_accept(generation)) {
        setState(() {
          _error = _failure(error);
          final status = error is ApiException ? error.statusCode : null;
          if (status == 422 || status == 409 || status == 429) {
            _pending?.close();
            _pending = null;
          }
          if (status == null || status >= 500) {
            _question.clear();
            _turn = null;
            _reviewed = false;
            _turnExpiry?.cancel();
          }
          if (status == 409) {
            _turn = null;
            _reviewed = false;
            _turnExpiry?.cancel();
          }
          if (status == 404) {
            _erase();
            _error = _failure(error);
          }
          if ([401, 403].contains(status)) {
            _erase();
            _pending?.close();
            _pending = null;
            _retired = true;
            _error = _failure(error);
          }
          // An unknown or 404 outcome never mints a replacement UUID.
        });
      }
    } finally {
      if (_active(generation)) setState(() => _busy = false);
    }
  }

  Map<String, dynamic> _operation() => {'operation_id': const Uuid().v4()};
  void _invalidateReview() {
    _turn = null;
    _reviewed = false;
    _turnExpiry?.cancel();
  }

  void _prepare() => _submit(AssistanceWrite.answer, {
    ..._operation(),
    'documents': _selected.toList(),
    'include_calendar': _calendar,
    'include_records': _records,
    'language': _language,
    'question': _question.text.trim(),
  }, learner: widget.learnerId);
  void _translate(SchoolAssistanceMessage message) => _submit(
    widget.office
        ? AssistanceWrite.officeTranslation
        : AssistanceWrite.translation,
    {..._operation(), 'message_id': message.id, 'language': _language},
    learner: _thread!.learnerId,
  );
  Widget _field(
    TextEditingController controller,
    String label, {
    int lines = 1,
    int maximum = 4000,
    VoidCallback? changed,
  }) => TextField(
    controller: controller,
    enabled: _canWrite,
    minLines: lines,
    maxLines: lines + 3,
    maxLength: maximum,
    decoration: InputDecoration(labelText: l(label)),
    onChanged: (_) => setState(() {
      changed?.call();
    }),
  );
  Widget _button(String label, VoidCallback? action, {Key? key}) =>
      FilledButton(key: key, onPressed: action, child: AcademicText(label));
  Widget _card(String title, List<Widget> children) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(l(title), style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    ),
  );
  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: widget.office
        ? l('School support office')
        : '${l('Ask the school')}${widget.learnerName == null ? '' : ' · ${widget.learnerName}'}',
    body: !_foreground
        ? const SizedBox.shrink()
        : !_configReady
        ? Center(
            child: _loading
                ? const CircularProgressIndicator()
                : Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      AcademicText(
                        _error ??
                            'Reopen this screen to check your school access.',
                      ),
                      TextButton(
                        onPressed: _retired ? null : _load,
                        child: const AcademicText('Check current access'),
                      ),
                    ],
                  ),
          )
        : ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (_error != null)
                Text(
                  l(_error!),
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              if (_notice != null) AcademicText(_notice!),
              if (_loading || _busy) const LinearProgressIndicator(),
              if (_config?.writable != true)
                const AcademicText(
                  'Writing is unavailable for this view. Existing documents and conversations remain available.',
                ),
              if (_pending != null)
                _card('Unconfirmed request', [
                  const AcademicText(
                    'Only the exact submitted request can be retried after current access is checked.',
                  ),
                  if (!_pending!.expired)
                    _button(
                      'Retry exact submitted request',
                      !_busy && !_loading && _config?.writable == true
                          ? _sendPending
                          : null,
                    )
                  else ...[
                    const AcademicText(
                      'The pending text expired. Its previous request may already have been saved.',
                    ),
                    TextButton(
                      onPressed: !_busy && !_loading ? _load : null,
                      child: const AcademicText('Check current saved records'),
                    ),
                    if (_expiredChecked)
                      TextButton(
                        onPressed: () => setState(() {
                          _pending?.close();
                          _pending = null;
                          _pendingExpiry?.cancel();
                          _notice =
                              'Check saved records before starting a separate new request.';
                        }),
                        child: const AcademicText(
                          'I checked saved records; start a separate request',
                        ),
                      ),
                  ],
                ]),
              if (_pending == null)
                TextButton(
                  onPressed: !_busy ? _load : null,
                  child: const AcademicText('Refresh current information'),
                ),
              DropdownButtonFormField<String>(
                isExpanded: true,
                initialValue: _language,
                decoration: InputDecoration(
                  labelText: l('Message or answer language'),
                ),
                items: _config!.languages
                    .map(
                      (language) => DropdownMenuItem(
                        value: language,
                        child: Text(assistanceLanguageName(language)),
                      ),
                    )
                    .toList(),
                onChanged: _canWrite
                    ? (value) => setState(() {
                        _language = value!;
                        _invalidateReview();
                      })
                    : null,
              ),
              _documentsCard(),
              if (_document != null)
                _card(_document!.title, [
                  ContentMarkdownView(markdown: _document!.markdown),
                  Text(_document!.sourceReference),
                ]),
              if (!widget.office) _questionCard(),
              if (_turn != null) _reviewCard(),
              _threadsCard(),
              if (_thread != null) _threadCard(),
              if (widget.office) _publicationCard(),
            ],
          ),
  );
  bool get _configReady => _config != null;
  Widget _documentsCard() => _card('Approved school documents', [
    for (final doc in _documents?.items ?? <SchoolAssistanceDocument>[])
      ListTile(
        contentPadding: EdgeInsets.zero,
        title: Text(doc.title),
        subtitle: Text(doc.sourceReference),
        leading: widget.office
            ? null
            : Checkbox(
                value: _selected.contains(doc.id),
                onChanged: _canWrite
                    ? (checked) => setState(() {
                        if (checked == true && _selected.length < 5) {
                          _selected.add(doc.id);
                        } else {
                          _selected.remove(doc.id);
                        }
                        _invalidateReview();
                      })
                    : null,
              ),
        trailing: TextButton(
          onPressed: _canRead ? () => _readDocument(doc.id) : null,
          child: const AcademicText('Read source'),
        ),
      ),
    if ((_documents?.items.length ?? 0) == 0)
      const AcademicText(
        'The school has not published a document for this selection yet.',
      ),
    Wrap(
      alignment: WrapAlignment.spaceBetween,
      spacing: 8,
      runSpacing: 4,
      children: [
        TextButton(
          onPressed: _canRead && _docPage > 1
              ? () {
                  _docPage--;
                  _load();
                }
              : null,
          child: const AcademicText('Previous documents'),
        ),
        TextButton(
          onPressed: _canRead && _docPage < (_documents?.lastPage ?? 1)
              ? () {
                  _docPage++;
                  _load();
                }
              : null,
          child: const AcademicText('More documents'),
        ),
      ],
    ),
  ]);
  Widget _questionCard() => _card('A sourced school answer', [
    if (!_config!.answerAvailable)
      const AcademicText(
        'School answers are currently unavailable. You can still read approved sources or contact the school office.',
      ),
    const AcademicText(
      'For school administration. For urgent safety or wellbeing concerns, use Report a concern or contact a trusted adult.',
    ),
    CheckboxListTile(
      contentPadding: EdgeInsets.zero,
      title: const AcademicText('Include the school calendar'),
      value: _calendar,
      onChanged: _canWrite
          ? (value) => setState(() {
              _calendar = value!;
              _invalidateReview();
            })
          : null,
    ),
    CheckboxListTile(
      contentPadding: EdgeInsets.zero,
      title: const AcademicText(
        'Include my child’s released results and attendance',
      ),
      value: _records,
      onChanged: _canWrite
          ? (value) => setState(() {
              _records = value!;
              _invalidateReview();
            })
          : null,
    ),
    _field(
      _question,
      'Your school question',
      lines: 2,
      maximum: 2000,
      changed: () {
        _turn = null;
        _reviewed = false;
        _turnExpiry?.cancel();
      },
    ),
    _button(
      'Review selected sources and question',
      _canWrite &&
              _question.text.trim().isNotEmpty &&
              (_selected.isNotEmpty || _calendar || _records)
          ? _prepare
          : null,
    ),
    const SizedBox(height: 8),
    _button(
      'Ask the school office',
      _canWrite && _question.text.trim().isNotEmpty
          ? () => _submit(AssistanceWrite.open, {
              ..._operation(),
              'text': _question.text.trim(),
              'language': _language,
            }, learner: widget.learnerId)
          : null,
    ),
    const AcademicText(
      'Only the question you choose to send enters the office thread. Academic source records are not copied into it.',
    ),
  ]);
  Widget _reviewCard() => _card('Review before sharing', [
    const AcademicText(
      'Known names were minimised. Check the exact text for other visible names or identifiers before sharing.',
    ),
    SelectableText(assistanceReviewText(_turn!, label: l)),
    if (_turn!.response == null) ...[
      CheckboxListTile(
        contentPadding: EdgeInsets.zero,
        value: _reviewed,
        onChanged: _canWrite
            ? (value) => setState(() => _reviewed = value!)
            : null,
        title: const AcademicText(
          'I reviewed this exact text and it contains no identifying details requiring removal',
        ),
      ),
      _button(
        'Request reviewed answer or translation',
        _canWrite &&
                _reviewed &&
                (_turn!.purpose.value == 'answer'
                    ? _config!.answerAvailable
                    : _config!.translationAvailable)
            ? () => _submit(AssistanceWrite.run, {
                'identifiers_reviewed': true,
              }, reference: _turn!.id)
            : null,
      ),
    ] else ...[
      const Divider(),
      SelectableText(_turn!.response!.text),
      for (final citation in _turn!.citations.where(
        (citation) => _turn!.response!.citations.contains(citation.token),
      ))
        ListTile(
          contentPadding: EdgeInsets.zero,
          title: Text(citation.title),
          trailing:
              citation.kind.value == 'document' && citation.locator.id != null
              ? TextButton(
                  onPressed: _idle
                      ? () => _readDocument(citation.locator.id!)
                      : null,
                  child: const AcademicText('Read source'),
                )
              : null,
        ),
      if (_turn!.response!.handoff)
        const AcademicText(
          'The sources do not settle this question. Ask the school office for help.',
        ),
    ],
    TextButton(
      onPressed: _busy || _loading
          ? null
          : () => setState(() {
              _turn = null;
              _reviewed = false;
              _turnExpiry?.cancel();
            }),
      child: const AcademicText('Close private review'),
    ),
  ]);
  Widget _threadsCard() => _card(
    widget.office ? 'School support queue' : 'Your school-support threads',
    [
      if (widget.office)
        for (final row
            in _queue?.items ?? <SchoolAssistanceOfficeThreadMetadata>[])
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: AcademicText(
              row.assignedToReader
                  ? 'Your assigned request'
                  : 'School support request',
            ),
            subtitle: Text('${row.state.value} · ${row.createdAt.toLocal()}'),
            trailing: TextButton(
              onPressed: (row.assignedToReader ? _canRead : _canWrite)
                  ? () {
                      if (row.assignedToReader) {
                        _readThread(row.id);
                      } else {
                        _submit(AssistanceWrite.claim, {
                          ..._operation(),
                          'expected_revision': row.revision,
                        }, reference: row.id);
                      }
                    }
                  : null,
              child: AcademicText(
                row.assignedToReader ? 'Open thread' : 'Claim request',
              ),
            ),
          )
      else
        for (final row in _threads?.items ?? <SchoolAssistanceThreadMetadata>[])
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: const AcademicText('Your school-support thread'),
            subtitle: Text('${row.state.value} · ${row.createdAt.toLocal()}'),
            trailing: TextButton(
              onPressed: _canRead ? () => _readThread(row.id) : null,
              child: const AcademicText('Open thread'),
            ),
          ),
      Wrap(
        alignment: WrapAlignment.spaceBetween,
        spacing: 8,
        runSpacing: 4,
        children: [
          TextButton(
            onPressed: _canRead && _threadPage > 1
                ? () {
                    _threadPage--;
                    _load();
                  }
                : null,
            child: const AcademicText('Previous requests'),
          ),
          TextButton(
            onPressed:
                _canRead &&
                    _threadPage <
                        (widget.office
                            ? _queue?.lastPage ?? 1
                            : _threads?.lastPage ?? 1)
                ? () {
                    _threadPage++;
                    _load();
                  }
                : null,
            child: const AcademicText('More requests'),
          ),
        ],
      ),
    ],
  );
  Widget _threadCard() => _card('Private school-support conversation', [
    for (final message in _thread!.messages)
      Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          AcademicText(
            message.authorKind.value == 'office' ? 'School office' : 'Parent',
          ),
          SelectableText(message.text),
          TextButton(
            onPressed: _canWrite ? () => _translate(message) : null,
            child: const AcademicText('Review message translation'),
          ),
          const Divider(),
        ],
      ),
    const AcademicText(
      'The original message remains available. A translation is never sent as a new message.',
    ),
    if (_thread!.state.value == 'open') ...[
      _field(_reply, 'Your reply', lines: 2),
      _button(
        'Send reply',
        _canWrite && _reply.text.trim().isNotEmpty
            ? () => _submit(AssistanceWrite.reply, {
                ..._operation(),
                'persona': _persona,
                'text': _reply.text.trim(),
                'language': _language,
              }, reference: _thread!.id)
            : null,
      ),
      TextButton(
        onPressed: _canWrite
            ? () => _submit(AssistanceWrite.close, {
                ..._operation(),
                'persona': _persona,
                'expected_revision': _thread!.revision,
              }, reference: _thread!.id)
            : null,
        child: const AcademicText('Close resolved thread'),
      ),
      if (widget.office) ...[
        DropdownButtonFormField<int>(
          isExpanded: true,
          initialValue: _assignee,
          decoration: InputDecoration(labelText: l('New office reader')),
          items: (_staff?.staff ?? <SchoolAssistanceStaffChoice>[])
              .map(
                (person) => DropdownMenuItem(
                  value: person.id,
                  child: Text(
                    person.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              )
              .toList(),
          onChanged: _canWrite
              ? (value) => setState(() => _assignee = value)
              : null,
        ),
        _field(_reason, 'Reason for office handoff', maximum: 1000),
        _button(
          'Confirm office handoff',
          _canWrite && _assignee != null && _reason.text.trim().isNotEmpty
              ? () => _submit(AssistanceWrite.reassign, {
                  ..._operation(),
                  'expected_revision': _thread!.revision,
                  'assigned_user_id': _assignee,
                  'reason': _reason.text.trim(),
                }, reference: _thread!.id)
              : null,
        ),
      ],
    ] else
      const AcademicText(
        'This resolved thread is read-only. Start a new school question if needed.',
      ),
    if (_thread!.page > 1)
      TextButton(
        onPressed: _canRead
            ? () => _readThread(_thread!.id, page: _thread!.page - 1)
            : null,
        child: const AcademicText('Newer replies'),
      ),
    if (_thread!.page < _thread!.lastPage)
      TextButton(
        onPressed: _canRead
            ? () => _readThread(_thread!.id, page: _thread!.page + 1)
            : null,
        child: const AcademicText('Older replies'),
      ),
    TextButton(
      onPressed: _busy || _loading
          ? null
          : () => setState(() {
              _thread = null;
              _reply.clear();
              _staff = null;
              _assignee = null;
            }),
      child: const AcademicText('Close private conversation'),
    ),
  ]);
  Widget _publicationCard() => _card('Publish approved school information', [
    _field(
      _title,
      'Document title',
      maximum: 191,
      changed: () => _documentReviewed = false,
    ),
    _field(
      _markdown,
      'Approved school text',
      lines: 4,
      maximum: 16000,
      changed: () => _documentReviewed = false,
    ),
    _field(
      _reference,
      'Document source or maintenance reference',
      maximum: 2048,
      changed: () => _documentReviewed = false,
    ),
    _field(
      _expiry,
      'Optional expiry date YYYY-MM-DD',
      maximum: 10,
      changed: () => _documentReviewed = false,
    ),
    CheckboxListTile(
      contentPadding: EdgeInsets.zero,
      title: const AcademicText(
        'I approve this exact school document and reviewed identifying details',
      ),
      value: _documentReviewed,
      onChanged: _canWrite
          ? (value) => setState(() => _documentReviewed = value!)
          : null,
    ),
    _button(
      _document == null
          ? 'Publish approved document'
          : 'Publish new document version',
      _canWrite &&
              _documentReviewed &&
              _title.text.trim().isNotEmpty &&
              _markdown.text.trim().isNotEmpty &&
              _reference.text.trim().isNotEmpty
          ? () => _submit(AssistanceWrite.publish, {
              ..._operation(),
              'expected_revision': _document?.revision ?? 0,
              if (_document != null) 'document_key': _document!.documentKey,
              'title': _title.text.trim(),
              'markdown': _markdown.text.trim(),
              'source_reference': _reference.text.trim(),
              'identifiers_reviewed': true,
              'expires_on': _expiry.text.trim().isEmpty
                  ? null
                  : _expiry.text.trim(),
            })
          : null,
    ),
    if (_document != null) ...[
      TextButton(
        onPressed: _canWrite
            ? () => _submit(
                AssistanceWrite.withdraw,
                _operation(),
                reference: _document!.id,
              )
            : null,
        child: const AcademicText('Withdraw this document version'),
      ),
      TextButton(
        onPressed: _canWrite
            ? () => setState(() {
                _document = null;
                _title.clear();
                _markdown.clear();
                _reference.clear();
                _expiry.clear();
                _documentReviewed = false;
              })
            : null,
        child: const AcademicText('Start a separate document'),
      ),
    ],
  ]);
}

/// Render the reviewed meaning without exposing wire JSON as the product flow.
String assistanceReviewText(
  SchoolAssistanceTurn turn, {
  String Function(String)? label,
}) {
  final text = label ?? (String value) => value;
  final data = Map<String, dynamic>.from(jsonDecode(turn.reviewText) as Map);
  if (data['task'] == 'translate') return data['text'] as String;
  final lines = <String>[data['question'] as String];
  for (final source in data['sources'] as List) {
    final row = Map<String, dynamic>.from(source as Map);
    final title = turn.citations
        .firstWhere((citation) => citation.token == row['source'])
        .title;
    final body = Map<String, dynamic>.from(
      jsonDecode(row['text'] as String) as Map,
    );
    lines.add('\n${body['title'] ?? title}');
    if (body['on_date'] is String) lines.add(body['on_date'] as String);
    if (body['markdown'] is String) {
      lines.add(body['markdown'] as String);
    }
    for (final result in (body['results'] as List?) ?? const []) {
      final percent = result['percentage_bp'];
      lines.add(
        '${result['subject_name']} · ${percent == null ? text('Not released') : '${percent / 100}%'}${result['provisional'] == true ? ' (${text('provisional')})' : ''}',
      );
    }
    for (final entry in (body['entries'] as List?) ?? const []) {
      lines.add('${entry['kind']}: ${entry['state']}');
    }
    for (final kind in ['terms', 'holidays', 'events']) {
      for (final item in (body[kind] as List?) ?? const []) {
        lines.add(
          '${item['name'] ?? item['title']} · ${item['starts_on'] ?? item['date'] ?? item['start_date'] ?? ''}${item['ends_on'] == null ? '' : ' – ${item['ends_on']}'}',
        );
      }
    }
    if (body['reference'] is String) lines.add(body['reference'] as String);
  }
  return lines.join('\n');
}

String assistanceLanguageName(String language) =>
    const <String, String>{
      'en': 'English',
      'af': 'Afrikaans',
      'zu': 'isiZulu',
      'xh': 'isiXhosa',
      'st': 'Sesotho',
      'tn': 'Setswana',
      'ts': 'itsonga',
      've': 'Tshivenda',
      'ss': 'siSwati',
      'nr': 'isiNdebele',
      'nso': 'Sepedi',
      'ar': 'العربية',
      'fr': 'Français',
      'es': 'Español',
      'pt-BR': 'Português (Brasil)',
      'tr': 'Türkçe',
      'ur': 'اردو',
      'id': 'Bahasa Indonesia',
    }[language] ??
    language;
