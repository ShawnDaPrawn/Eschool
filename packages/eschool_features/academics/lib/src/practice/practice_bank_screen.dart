import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';
import '../localization/academic_text.dart';
import 'practice_answers.dart';
import 'practice_repository.dart';

/// A teacher explicitly approves each immutable self-study question revision.
class PracticeBankScreen extends StatefulWidget {
  const PracticeBankScreen({
    required this.repository,
    required this.canRead,
    required this.canWrite,
    super.key,
  });
  final PracticeRepository repository;
  final bool Function() canRead, canWrite;
  @override
  State<PracticeBankScreen> createState() => _PracticeBankScreenState();
}

class _PracticeBankScreenState extends State<PracticeBankScreen>
    with WidgetsBindingObserver {
  PracticeAccess? _access;
  PracticeBankChoices? _choices;
  PracticeBankPage? _bank;
  PracticeBankItem? _editing;
  PracticeIntent? _pending;
  final _prompt = TextEditingController(),
      _explanation = TextEditingController(),
      _answer = TextEditingController();
  final _options = List.generate(6, (_) => TextEditingController());
  final _correct = <int>{0};
  int? _group, _topic, _source;
  int _epoch = 0, _page = 1, _seconds = 60, _count = 2;
  bool _busy = true,
      _dead = false,
      _suspended = false,
      _editor = false,
      _enabled = true;
  PracticeQuestionKindEnum _kind = PracticeQuestionKindEnum.number;
  String _error = '', _status = '';
  final _scroll = ScrollController();
  String l(String value) => academicText(context, value);
  bool get _active =>
      mounted &&
      !_dead &&
      !_suspended &&
      widget.canRead() &&
      widget.repository.isActive;
  bool get _write =>
      _active &&
      widget.canWrite() &&
      _access?.writable == true &&
      _choices?.writable == true;
  bool _current(int epoch) => _active && epoch == _epoch;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void dispose() {
    _epoch++;
    WidgetsBinding.instance.removeObserver(this);
    _scroll.dispose();
    _prompt.dispose();
    _explanation.dispose();
    _answer.dispose();
    for (final c in _options) {
      c.dispose();
    }
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (!mounted) return;
    if (state != AppLifecycleState.resumed) {
      setState(() {
        _suspended = true;
        _epoch++;
        _access = null;
        _choices = null;
        _bank = null;
        _clear();
        _busy = false;
      });
    } else if (_suspended && !_dead) {
      _suspended = false;
      _load();
    }
  }

  void _clear() {
    _editing = null;
    _editor = false;
    _pending = null;
    _topic = null;
    _source = null;
    _prompt.clear();
    _explanation.clear();
    _answer.clear();
    for (final c in _options) {
      c.clear();
    }
    _correct.clear();
    _correct.add(0);
  }

  void _fail(Object error, int epoch) {
    if (!mounted || _epoch != epoch) return;
    setState(() {
      _busy = false;
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        _dead = true;
        _access = null;
        _bank = null;
        _choices = null;
        _clear();
        _error = 'Reopen practice to check your access.';
      } else if (error is ApiException &&
          [409, 422].contains(error.statusCode)) {
        _pending = null;
        _error =
            'The revision or input was refused. Refresh the bank before approving a changed revision.';
      } else if (error is FormatException) {
        _error = error.message;
      } else {
        _error =
            'The question could not be saved or refreshed. Retry the exact operation if its outcome is unknown.';
      }
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && epoch == _epoch && _scroll.hasClients) {
        _scroll.jumpTo(0);
      }
    });
  }

  Future<void> _load({int? group, int page = 1}) async {
    if (!_active || _pending != null) return;
    final epoch = ++_epoch;
    setState(() {
      _busy = true;
      _error = '';
      _bank = null;
      _choices = null;
      _page = page;
      _clear();
    });
    try {
      final access = await widget.repository.access();
      if (!_current(epoch)) return;
      final selected = group ?? _group;
      final id = access.staffGroups.any((g) => g.id == selected)
          ? selected
          : access.staffGroups.firstOrNull?.id;
      final choices = id == null ? null : await widget.repository.choices(id);
      final bank = id == null
          ? null
          : await widget.repository.bank(id, page: page);
      if (!_current(epoch)) return;
      setState(() {
        _access = access;
        _group = id;
        _choices = choices;
        _bank = bank;
        _busy = false;
      });
    } catch (e) {
      _fail(e, epoch);
    }
  }

  void _edit(PracticeBankItem? item) {
    if (!_write || _busy || _pending != null) return;
    setState(() {
      _clear();
      _editing = item;
      _editor = true;
      _status = '';
      _error = '';
      _kind = item?.definition.kind ?? PracticeQuestionKindEnum.number;
      _topic = item?.topicId;
      _source = item?.contentVersionId;
      _enabled = item?.active ?? true;
      _seconds = item?.estimatedSeconds ?? 60;
      _prompt.text = item?.definition.prompt ?? '';
      _explanation.text = item?.definition.explanation ?? '';
      _answer.text = _kind == PracticeQuestionKindEnum.number
          ? item?.definition.answers.join('\n') ?? ''
          : '';
      _count = item?.definition.options.length ?? 2;
      _correct.clear();
      for (var index = 0; index < _options.length; index++) {
        _options[index].text = index < (item?.definition.options.length ?? 0)
            ? item!.definition.options[index].text
            : '';
        if (index < (item?.definition.options.length ?? 0) &&
            item!.definition.answers.contains(
              item.definition.options[index].id,
            )) {
          _correct.add(index);
        }
      }
    });
  }

  Future<void> _approve() async {
    if (!_write || _busy || _pending != null || _group == null) return;
    try {
      if (_topic == null ||
          _source == null ||
          !_choices!.topics.any((t) => t.id == _topic) ||
          !_choices!.sources.any((s) => s.id == _source)) {
        throw const FormatException(
          'Choose a current published topic and an approved source.',
        );
      }
      if (_prompt.text.trim().isEmpty ||
          _explanation.text.trim().isEmpty ||
          _prompt.text.runes.length > 4000 ||
          _explanation.text.runes.length > 4000) {
        throw const FormatException(
          'Add a question and a worked explanation, each at most 4,000 characters.',
        );
      }
      final answers = _kind == PracticeQuestionKindEnum.number
          ? _answer.text
                .split('\n')
                .where((v) => v.trim().isNotEmpty)
                .map(LocalPracticeAnswers.number)
                .toSet()
                .toList()
          : _correct
                .where((v) => v < _count)
                .map((v) => 'option_${v + 1}')
                .toList();
      if (answers.isEmpty ||
          answers.length > 10 ||
          (_kind == PracticeQuestionKindEnum.choice &&
              _options.take(_count).any((c) => c.text.trim().isEmpty))) {
        throw const FormatException(
          'Add at least one accepted answer and complete every offered choice.',
        );
      }
      final intent = PracticeIntent.approve(
        _group!,
        PracticeApproveInput(
          operationId: const Uuid().v4(),
          expectedRevision: _editing?.revision ?? 0,
          topicId: _topic!,
          contentVersionId: _source!,
          estimatedSeconds: _seconds,
          active: _enabled,
          definition: PracticeQuestion(
            kind: _kind,
            prompt: _prompt.text.trim(),
            explanation: _explanation.text.trim(),
            options: _kind == PracticeQuestionKindEnum.number
                ? []
                : [
                    for (var i = 0; i < _count; i++)
                      PracticeQuestionOption(
                        id: 'option_${i + 1}',
                        text: _options[i].text.trim(),
                      ),
                  ],
            answers: answers,
          ),
        ),
        item: _editing?.id,
      );
      await _send(intent);
    } catch (e) {
      _fail(e, _epoch);
    }
  }

  Future<void> _send(PracticeIntent intent) async {
    if (!_write || _busy) return;
    final epoch = _epoch;
    setState(() {
      _busy = true;
      _pending = intent;
      _error = '';
    });
    try {
      await widget.repository.approve(intent);
      if (!_current(epoch)) return;
      setState(() {
        _pending = null;
        _status = 'Question revision approved.';
        _clear();
        _busy = false;
      });
      await _load(group: _group, page: _page);
    } catch (e) {
      _fail(e, epoch);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_active) {
      return ScreenScaffold(
        title: l('Practice question bank'),
        body: Center(
          child: AcademicText('Reopen practice to check your access.'),
        ),
      );
    }
    final unlocked = _write && !_busy && _pending == null;
    final validTopic = _choices?.topics.any((c) => c.id == _topic) == true
        ? _topic
        : null;
    final validSource = _choices?.sources.any((c) => c.id == _source) == true
        ? _source
        : null;
    return ScreenScaffold(
      title: l('Practice question bank'),
      body: ListView(
        controller: _scroll,
        padding: const EdgeInsets.all(16),
        children: [
          AcademicText(
            'Approve short questions from current school-approved sources. Each edit creates an immutable revision.',
          ),
          if (_busy) const LinearProgressIndicator(),
          if (_error.isNotEmpty)
            Text(
              l(_error),
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          if (_status.isNotEmpty) Text(l(_status)),
          if (_access != null)
            DropdownButtonFormField<int>(
              key: ValueKey('practice-group-$_group'),
              initialValue: _group,
              isExpanded: true,
              decoration: InputDecoration(labelText: l('Teaching group')),
              items: [
                for (final g in _access!.staffGroups)
                  DropdownMenuItem(
                    value: g.id,
                    child: Text('${g.subjectName} · ${g.name}'),
                  ),
              ],
              onChanged: !_busy && _pending == null
                  ? (g) => _load(group: g)
                  : null,
            ),
          if (_pending != null) ...[
            AcademicText(
              'The outcome is unknown. Retry this exact saved operation before changing it.',
            ),
            FilledButton(
              onPressed: !_busy && _write ? () => _send(_pending!) : null,
              child: AcademicText('Retry exact operation'),
            ),
          ],
          if (!_editor) ...[
            OutlinedButton(
              onPressed: !_busy && _pending == null
                  ? () => _load(group: _group, page: _page)
                  : null,
              child: AcademicText('Refresh question bank'),
            ),
            FilledButton.icon(
              onPressed: unlocked ? () => _edit(null) : null,
              icon: const Icon(Icons.add),
              label: AcademicText('New practice question'),
            ),
            for (final item in _bank?.items ?? <PracticeBankItem>[])
              Card(
                child: ListTile(
                  title: Text(
                    item.definition.prompt,
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                  ),
                  subtitle: Text(
                    '${l('Revision')} ${item.revision} · ${l(item.active ? 'Active' : 'Withdrawn')}',
                  ),
                  trailing: const Icon(Icons.edit_outlined),
                  onTap: unlocked ? () => _edit(item) : null,
                ),
              ),
            if (_bank != null)
              Row(
                children: [
                  TextButton(
                    onPressed: !_busy && _pending == null && _page > 1
                        ? () => _load(group: _group, page: _page - 1)
                        : null,
                    child: AcademicText('Previous'),
                  ),
                  Text('$_page/${_bank!.lastPage}'),
                  TextButton(
                    onPressed:
                        !_busy && _pending == null && _page < _bank!.lastPage
                        ? () => _load(group: _group, page: _page + 1)
                        : null,
                    child: AcademicText('Next'),
                  ),
                ],
              ),
            if (_access?.staffGroups.isEmpty == true)
              AcademicText(
                'No current allocated teaching groups are available.',
              ),
          ] else ...[
            if (_editing != null && (validTopic == null || validSource == null))
              AcademicText(
                'This revision’s source or topic is no longer available. Select a current approved source; create a new question to change its topic.',
              ),
            DropdownButtonFormField<int>(
              key: ValueKey('practice-topic-${_editing?.id}-$validTopic'),
              initialValue: validTopic,
              isExpanded: true,
              decoration: InputDecoration(labelText: l('Published topic')),
              items: [
                for (final c in _choices!.topics)
                  DropdownMenuItem(value: c.id, child: Text(c.name)),
              ],
              onChanged: unlocked && _editing == null
                  ? (id) => setState(() {
                      _topic = id;
                    })
                  : null,
            ),
            DropdownButtonFormField<int>(
              key: ValueKey('practice-source-${_editing?.id}-$validSource'),
              initialValue: validSource,
              isExpanded: true,
              decoration: InputDecoration(labelText: l('Approved source')),
              items: [
                for (final c in _choices!.sources)
                  DropdownMenuItem(value: c.id, child: Text(c.name)),
              ],
              onChanged: unlocked
                  ? (id) => setState(() {
                      _source = id;
                    })
                  : null,
            ),
            DropdownButtonFormField<PracticeQuestionKindEnum>(
              initialValue: _kind,
              decoration: InputDecoration(labelText: l('Question type')),
              items: [
                DropdownMenuItem(
                  value: PracticeQuestionKindEnum.number,
                  child: AcademicText('Decimal answer'),
                ),
                DropdownMenuItem(
                  value: PracticeQuestionKindEnum.choice,
                  child: AcademicText('Multiple choice'),
                ),
              ],
              onChanged: unlocked
                  ? (kind) => setState(() {
                      _kind = kind ?? PracticeQuestionKindEnum.number;
                    })
                  : null,
            ),
            TextField(
              controller: _prompt,
              enabled: unlocked,
              maxLength: 4000,
              minLines: 2,
              maxLines: 6,
              decoration: InputDecoration(labelText: l('Question')),
            ),
            TextField(
              controller: _explanation,
              enabled: unlocked,
              maxLength: 4000,
              minLines: 2,
              maxLines: 8,
              decoration: InputDecoration(labelText: l('Worked explanation')),
            ),
            if (_kind == PracticeQuestionKindEnum.number)
              TextField(
                controller: _answer,
                enabled: unlocked,
                maxLength: 1000,
                minLines: 2,
                maxLines: 4,
                decoration: InputDecoration(
                  labelText: l('Accepted decimals, one per line'),
                ),
              ),
            if (_kind == PracticeQuestionKindEnum.choice) ...[
              DropdownButtonFormField<int>(
                initialValue: _count,
                decoration: InputDecoration(labelText: l('Number of choices')),
                items: [
                  for (var i = 2; i <= 6; i++)
                    DropdownMenuItem(value: i, child: Text('$i')),
                ],
                onChanged: unlocked
                    ? (v) => setState(() {
                        _count = v ?? 2;
                      })
                    : null,
              ),
              for (var i = 0; i < _count; i++)
                Padding(
                  padding: const EdgeInsets.only(top: 8),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Checkbox(
                        value: _correct.contains(i),
                        onChanged: unlocked
                            ? (v) => setState(() {
                                if (v == true) {
                                  _correct.add(i);
                                } else {
                                  _correct.remove(i);
                                }
                              })
                            : null,
                      ),
                      Expanded(
                        child: TextField(
                          controller: _options[i],
                          enabled: unlocked,
                          maxLength: 1000,
                          decoration: InputDecoration(
                            labelText:
                                '${l('Choice')} ${i + 1} · ${l('tick accepted answers')}',
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
            ],
            DropdownButtonFormField<int>(
              initialValue: _seconds,
              decoration: InputDecoration(labelText: l('Estimated seconds')),
              items: [
                for (final i in ({
                  60,
                  90,
                  120,
                  180,
                  240,
                  300,
                  _seconds,
                }.toList()..sort()))
                  DropdownMenuItem(value: i, child: Text('$i')),
              ],
              onChanged: unlocked
                  ? (v) => setState(() {
                      _seconds = v ?? 60;
                    })
                  : null,
            ),
            SwitchListTile(
              value: _enabled,
              onChanged: unlocked
                  ? (v) => setState(() {
                      _enabled = v;
                    })
                  : null,
              title: AcademicText(
                'Available for new and existing practice sets',
              ),
            ),
            FilledButton(
              onPressed: unlocked ? _approve : null,
              child: AcademicText('Approve question revision'),
            ),
            TextButton(
              onPressed: !_busy && _pending == null
                  ? () => setState(_clear)
                  : null,
              child: AcademicText('Discard draft'),
            ),
          ],
        ],
      ),
    );
  }
}
