import 'dart:convert';

import 'package:eschool_api/eschool_api.dart';
import 'package:flutter/material.dart';

import '../localization/academic_text.dart';

/// Every editable line is local to one server revision. Approval cannot skip edits.
class TeachingDraftEditor extends StatefulWidget {
  const TeachingDraftEditor({
    required this.draft,
    required this.busy,
    required this.writable,
    required this.onEdit,
    required this.onApprove,
    this.onDirtyChanged,
    super.key,
  });
  final TeachingDraftDetail draft;
  final bool busy, writable;
  final ValueChanged<TeachingDraftDefinition> onEdit;
  final VoidCallback onApprove;
  final ValueChanged<bool>? onDirtyChanged;

  @override
  State<TeachingDraftEditor> createState() => _TeachingDraftEditorState();
}

class _TeachingDraftEditorState extends State<TeachingDraftEditor> {
  final _controllers = <String, TextEditingController>{};
  late final Map<String, dynamic> _definition;
  bool _dirty = false;

  @override
  void initState() {
    super.initState();
    _definition =
        jsonDecode(jsonEncode(widget.draft.definition!.toJson()))
            as Map<String, dynamic>;
  }

  TextEditingController _controller(String key, Object? initial) => _controllers
      .putIfAbsent(key, () => TextEditingController(text: '$initial'));

  Widget _field(
    String key,
    String label,
    String value,
    ValueChanged<String> change, {
    int maximum = 6000,
    bool numeric = false,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 14),
    child: TextField(
      key: ValueKey('teaching-edit-$key'),
      controller: _controller(key, value),
      enabled: widget.writable && !widget.busy,
      minLines: numeric ? 1 : 2,
      maxLines: numeric ? 1 : 10,
      maxLength: maximum,
      keyboardType: numeric
          ? const TextInputType.numberWithOptions(decimal: true)
          : TextInputType.multiline,
      decoration: InputDecoration(labelText: academicText(context, label)),
      onChanged: (text) {
        change(text);
        if (!_dirty) {
          setState(() => _dirty = true);
          widget.onDirtyChanged?.call(true);
        }
      },
    ),
  );

  @override
  void dispose() {
    for (final controller in _controllers.values) {
      controller.clear();
      controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final fields = <Widget>[];
    if (_definition['title'] case final String title) {
      fields.add(
        _field(
          'title',
          'Title',
          title,
          (value) => _definition['title'] = value,
          maximum: 240,
        ),
      );
    }
    if (_definition['sections'] case final List sections) {
      for (var i = 0; i < sections.length; i++) {
        final row = sections[i] as Map<String, dynamic>;
        fields.add(
          _field(
            'section-$i',
            switch (row['level']) {
              'support' => 'Support',
              'extension' => 'Extension',
              _ => 'Core',
            },
            row['body'] as String,
            (value) => row['body'] = value,
          ),
        );
      }
    }
    if (_definition['text'] case final String text) {
      fields.add(
        _field(
          'comment',
          'Report comment',
          text,
          (value) => _definition['text'] = value,
          maximum: 1600,
        ),
      );
    }
    if (_definition['questions'] case final List questions) {
      for (var i = 0; i < questions.length; i++) {
        final question = questions[i] as Map<String, dynamic>;
        final row = question['definition'] as Map<String, dynamic>;
        fields.add(
          Text(
            '${academicText(context, 'Question')} ${i + 1}',
            style: Theme.of(context).textTheme.titleMedium,
          ),
        );
        fields.add(
          _field(
            'q-$i',
            'Question',
            row['prompt'] as String,
            (value) => row['prompt'] = value,
            maximum: 4000,
          ),
        );
        final options = row['options'] as List;
        for (var j = 0; j < options.length; j++) {
          final option = options[j] as Map<String, dynamic>;
          fields.add(
            _field(
              'q-$i-option-$j',
              'Answer option',
              option['text'] as String,
              (value) => option['text'] = value,
              maximum: 2000,
            ),
          );
        }
        fields.add(
          _field(
            'q-$i-answers',
            'Accepted answers, one per line',
            (row['answers'] as List).join('\n'),
            (value) {
              row['answers'] = value
                  .split('\n')
                  .map((line) => line.trim())
                  .where((line) => line.isNotEmpty)
                  .toList();
            },
            maximum: 1000,
          ),
        );
        fields.add(
          _field(
            'q-$i-explanation',
            'Explanation',
            row['explanation'] as String,
            (value) => row['explanation'] = value,
            maximum: 4000,
          ),
        );
      }
    }
    if (_definition['question_marks'] case final List marks) {
      fields.add(
        const AcademicText(
          'These are proposals. Official marks change only after separate confirmation.',
        ),
      );
      for (var i = 0; i < marks.length; i++) {
        final row = marks[i] as Map<String, dynamic>;
        final labels = widget.draft.questionLabels.where(
          (label) => label.questionId == row['question_id'],
        );
        final label = labels.isEmpty ? null : labels.first;
        fields.add(
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Text(
              '${academicText(context, 'Question')} ${label?.number ?? i + 1}'
              ' · ${academicText(context, 'Maximum')} ${label?.maxMark ?? ''}'
              ' · ${academicText(context, 'Confidence')} ${(row['confidence_bp'] as int) / 100}%',
            ),
          ),
        );
        fields.add(
          _field(
            'mark-$i',
            'Proposed mark',
            row['mark'] as String? ?? '',
            (value) => row['mark'] = value.trim().isEmpty ? null : value.trim(),
            maximum: 12,
            numeric: true,
          ),
        );
        fields.add(
          _field(
            'reason-$i',
            'Working evidence',
            row['reason'] as String,
            (value) => row['reason'] = value,
            maximum: 1000,
          ),
        );
      }
    }
    return Directionality(
      textDirection: widget.draft.request?.language.value == 'ar'
          ? TextDirection.rtl
          : TextDirection.ltr,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          ...fields,
          Wrap(
            spacing: 12,
            runSpacing: 8,
            children: [
              FilledButton.icon(
                key: const ValueKey('teaching-save-edit'),
                onPressed: widget.writable && !widget.busy && _dirty
                    ? () => widget.onEdit(
                        TeachingDraftDefinition.fromJson(
                          jsonDecode(jsonEncode(_definition))
                              as Map<String, dynamic>,
                        ),
                      )
                    : null,
                icon: const Icon(Icons.save_outlined),
                label: const AcademicText('Save edits'),
              ),
              OutlinedButton.icon(
                key: const ValueKey('teaching-approve'),
                onPressed:
                    widget.writable &&
                        !widget.busy &&
                        !_dirty &&
                        widget.draft.state.value == 'ready'
                    ? widget.onApprove
                    : null,
                icon: const Icon(Icons.check),
                label: const AcademicText('Approve draft'),
              ),
            ],
          ),
          if (_dirty)
            const Padding(
              padding: EdgeInsets.only(top: 8),
              child: AcademicText(
                'Save your edits before approving this revision.',
              ),
            ),
        ],
      ),
    );
  }
}
