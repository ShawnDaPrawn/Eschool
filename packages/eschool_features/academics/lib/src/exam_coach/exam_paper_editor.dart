import 'package:eschool_api/eschool_api.dart';
import 'package:flutter/material.dart';
import 'package:uuid/uuid.dart';

import '../localization/academic_text.dart';
import 'exam_repository.dart';

class _Question {
  _Question([ExamCoachQuestion? q])
    : number = TextEditingController(text: q?.number ?? ''),
      prompt = TextEditingController(text: q?.prompt ?? ''),
      memo = TextEditingController(text: q?.memo ?? ''),
      units = TextEditingController(
        text: q == null ? '1' : examMarkText(q.maxUnits),
      ),
      topics = {...?q?.topicIds};
  final TextEditingController number, prompt, memo, units;
  final Set<int> topics;
  void dispose() {
    for (final c in [number, prompt, memo, units]) {
      c.dispose();
    }
  }
}

/// Human licence attestation and exact question/memo review precede publication.
class ExamPaperEditor extends StatefulWidget {
  const ExamPaperEditor({
    required this.choices,
    required this.group,
    required this.onSave,
    required this.onCancel,
    this.paper,
    this.initialWrite,
    super.key,
  });
  final ExamCoachStaffChoices choices;
  final int group;
  final ExamCoachPaperVersion? paper;
  final ExamCoachPaperWrite? initialWrite;
  final ValueChanged<ExamCoachPaperWrite> onSave;
  final VoidCallback onCancel;
  @override
  State<ExamPaperEditor> createState() => _ExamPaperEditorState();
}

class _ExamPaperEditorState extends State<ExamPaperEditor> {
  final _form = GlobalKey<FormState>();
  late final TextEditingController _title,
      _year,
      _code,
      _reference,
      _statement,
      _expires;
  late final List<_Question> _questions;
  late String _framework, _licence;
  late Set<int> _groups;
  bool _confirmed = false;
  String? _error;
  @override
  void initState() {
    super.initState();
    final p = widget.paper;
    final d = widget.initialWrite;
    _title = TextEditingController(text: d?.title ?? p?.title ?? '');
    _year = TextEditingController(
      text: (d?.paperYear ?? p?.paperYear ?? DateTime.now().year).toString(),
    );
    _code = TextEditingController(text: d?.paperCode ?? p?.paperCode ?? '');
    _reference = TextEditingController(
      text: d?.licence.reference ?? p?.licence?.reference ?? '',
    );
    _statement = TextEditingController(
      text: d?.licence.statement ?? p?.licence?.statement ?? '',
    );
    _expires = TextEditingController(
      text: d?.licence.expiresOn ?? p?.licence?.expiresOn ?? '',
    );
    _framework =
        d?.framework ?? p?.framework ?? widget.choices.frameworks.first;
    _licence = d?.licence.kind.value ?? p?.licence?.kind.value ?? 'permission';
    _groups = {...?d?.groupIds, ...?p?.groupIds, widget.group};
    _questions = d != null
        ? d.questions
              .map((q) => _Question(ExamCoachQuestion.fromJson(q.toJson())))
              .toList()
        : p == null
        ? [_Question()]
        : p.questions.map(_Question.new).toList();
  }

  @override
  void dispose() {
    for (final c in [_title, _year, _code, _reference, _statement, _expires]) {
      c.dispose();
    }
    for (final q in _questions) {
      q.dispose();
    }
    super.dispose();
  }

  Widget field(
    String label,
    TextEditingController c, {
    int lines = 1,
    int maximum = 500,
    int minimum = 1,
    bool integer = false,
  }) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 6),
    child: TextFormField(
      controller: c,
      decoration: InputDecoration(labelText: academicText(context, label)),
      maxLength: maximum,
      minLines: lines,
      maxLines: lines,
      keyboardType: integer ? TextInputType.number : TextInputType.multiline,
      validator: (v) => (v?.trim().length ?? 0) < minimum
          ? academicText(context, 'Required')
          : null,
    ),
  );
  void _save() {
    if (!_form.currentState!.validate()) return;
    final year = int.tryParse(_year.text);
    final numbers = _questions.map((q) => q.number.text.trim()).toSet();
    final validTopics = widget.choices.topics.map((t) => t.id).toSet();
    if (!_confirmed ||
        year == null ||
        year < 1850 ||
        year > 2100 ||
        _groups.isEmpty ||
        _groups.length > 20 ||
        numbers.length != _questions.length ||
        _questions.any(
          (q) =>
              q.topics.isEmpty ||
              q.topics.length > 10 ||
              !RegExp(
                r'^[A-Za-z0-9().-]{1,16}$',
              ).hasMatch(q.number.text.trim()) ||
              !validTopics.containsAll(q.topics) ||
              examMarkUnits(q.units.text) == null ||
              examMarkUnits(q.units.text)! < 1,
        ) ||
        (_expires.text.isNotEmpty &&
            !RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(_expires.text))) {
      setState(
        () => _error =
            'Check the licence, date, distinct question numbers and topic shares.',
      );
      return;
    }
    widget.onSave(
      ExamCoachPaperWrite.fromJson({
        'operation_id': const Uuid().v4(),
        'expected_revision':
            widget.initialWrite?.expectedRevision ??
            widget.paper?.revision ??
            0,
        'title': _title.text.trim(),
        'framework': _framework,
        'paper_year': year,
        'paper_code': _code.text.trim(),
        'group_ids': _groups.toList(),
        'licence': {
          'kind': _licence,
          'reference': _reference.text.trim(),
          'statement': _statement.text.trim(),
          'confirmed': true,
          if (_expires.text.isNotEmpty) 'expires_on': _expires.text,
        },
        'questions': _questions
            .map(
              (q) => {
                'number': q.number.text.trim(),
                'prompt': q.prompt.text.trim(),
                'memo': q.memo.text.trim(),
                'max_units': examMarkUnits(q.units.text)!,
                'topic_ids': q.topics.toList(),
              },
            )
            .toList(),
      }),
    );
  }

  @override
  Widget build(BuildContext context) => Form(
    key: _form,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const AcademicText(
          'Review the paper, memo and permission before publishing.',
        ),
        field('Paper title', _title, maximum: 191),
        field('Paper year', _year, maximum: 4, integer: true),
        field('Paper code', _code, maximum: 64),
        DropdownButtonFormField<String>(
          initialValue: _framework,
          isExpanded: true,
          decoration: InputDecoration(
            labelText: academicText(context, 'Framework'),
          ),
          items: widget.choices.frameworks
              .map((s) => DropdownMenuItem(value: s, child: Text(s)))
              .toList(),
          onChanged: (v) => setState(() => _framework = v!),
        ),
        const AcademicText('Groups that may use this paper'),
        for (final g in widget.choices.audienceGroups)
          CheckboxListTile(
            value: _groups.contains(g.id),
            title: Text(g.name),
            controlAffinity: ListTileControlAffinity.leading,
            onChanged: (v) => setState(
              () => v == true ? _groups.add(g.id) : _groups.remove(g.id),
            ),
          ),
        DropdownButtonFormField<String>(
          initialValue: _licence,
          isExpanded: true,
          decoration: InputDecoration(
            labelText: academicText(context, 'Permission basis'),
          ),
          items: const [
            'permission',
            'open_licence',
            'school_owned',
          ].map((s) => DropdownMenuItem(value: s, child: Text(s))).toList(),
          onChanged: (v) => setState(() => _licence = v!),
        ),
        field('Permission reference', _reference, maximum: 1000, minimum: 3),
        field(
          'Permission statement',
          _statement,
          lines: 3,
          maximum: 2000,
          minimum: 20,
        ),
        TextFormField(
          controller: _expires,
          decoration: InputDecoration(
            labelText: academicText(
              context,
              'Permission expiry (YYYY-MM-DD, optional)',
            ),
          ),
        ),
        CheckboxListTile(
          value: _confirmed,
          onChanged: (v) => setState(() => _confirmed = v ?? false),
          title: const AcademicText(
            'I checked the school has permission to use this paper and memo.',
          ),
        ),
        for (var i = 0; i < _questions.length; i++)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                children: [
                  Text('${academicText(context, 'Question')} ${i + 1}'),
                  field('Question number', _questions[i].number, maximum: 20),
                  field(
                    'Question text',
                    _questions[i].prompt,
                    lines: 4,
                    maximum: 4000,
                  ),
                  field(
                    'Staff memo',
                    _questions[i].memo,
                    lines: 4,
                    maximum: 8000,
                  ),
                  field(
                    'Maximum marks',
                    _questions[i].units,
                    integer: true,
                    maximum: 6,
                  ),
                  const AcademicText('Topics used to weight study time'),
                  for (final t in widget.choices.topics)
                    CheckboxListTile(
                      value: _questions[i].topics.contains(t.id),
                      title: Text(t.name),
                      onChanged: (v) => setState(
                        () => v == true
                            ? _questions[i].topics.add(t.id)
                            : _questions[i].topics.remove(t.id),
                      ),
                    ),
                  if (_questions.length > 1)
                    TextButton(
                      onPressed: () =>
                          setState(() => _questions.removeAt(i).dispose()),
                      child: const AcademicText('Remove question'),
                    ),
                ],
              ),
            ),
          ),
        if (_questions.length < 20)
          OutlinedButton(
            onPressed: () => setState(() => _questions.add(_Question())),
            child: const AcademicText('Add question'),
          ),
        if (_error != null) AcademicText(_error!),
        FilledButton(
          onPressed: _save,
          child: const AcademicText('Save reviewed draft'),
        ),
        TextButton(
          onPressed: widget.onCancel,
          child: const AcademicText('Cancel'),
        ),
      ],
    ),
  );
}
