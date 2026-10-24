import '../localization/academic_text.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'report_comment_cubit.dart';
import 'report_family_cubit.dart';
import 'report_repository.dart';

const reportSectionNames = {
  'subjects': 'Subject results',
  'attendance': 'Attendance',
  'subject_comments': 'Subject comments',
  'homeroom_comment': 'Homeroom comment',
};

class ReportDesignScreen extends StatefulWidget {
  const ReportDesignScreen({
    required this.repository,
    required this.configuration,
    super.key,
  });
  final ReportRepository repository;
  final AcademicReportConfigurationData configuration;
  @override
  State<ReportDesignScreen> createState() => _ReportDesignScreenState();
}

class _ReportDesignScreenState extends State<ReportDesignScreen> {
  final _name = TextEditingController(),
      _heading = TextEditingController(text: 'School report'),
      _intro = TextEditingController(),
      _footer = TextEditingController();
  final _bankLabel = TextEditingController(),
      _bankText = TextEditingController();
  final _keys = ReportOperationKeys();
  late List<AcademicReportTemplate> _templates = List.of(
    widget.configuration.templates,
  );
  late List<AcademicReportCommentBank> _banks = List.of(
    widget.configuration.commentBanks,
  );
  AcademicReportTemplate? _template;
  AcademicReportCommentBank? _bank;
  List<String> _sections = reportSectionNames.keys.toList();
  bool _crest = true, _active = true, _busy = false;
  int? _subject;
  String? _error, _notice;
  @override
  void dispose() {
    for (final c in [_name, _heading, _intro, _footer, _bankLabel, _bankText]) {
      c.dispose();
    }
    super.dispose();
  }

  void _editTemplate(AcademicReportTemplate? value) {
    setState(() {
      _template = value;
      _name.text = value?.name ?? '';
      _heading.text = value?.design.heading ?? 'School report';
      _intro.text = value?.design.introduction ?? '';
      _footer.text = value?.design.footer ?? '';
      _sections = List.of(value?.design.sections ?? reportSectionNames.keys);
      _crest = value?.design.showCrest ?? true;
      _error = null;
      _notice = null;
    });
  }

  void _editBank(AcademicReportCommentBank? value) {
    setState(() {
      _bank = value;
      _bankLabel.text = value?.label ?? '';
      _bankText.text = value?.text ?? '';
      _subject = value?.subjectId;
      _active = value?.active ?? true;
      _error = null;
      _notice = null;
    });
  }

  Future<void> _save(bool design) async {
    if (_busy) return;
    if (design && (_name.text.trim().isEmpty || _heading.text.trim().isEmpty) ||
        !design &&
            (_bankLabel.text.trim().isEmpty || _bankText.text.trim().isEmpty)) {
      setState(
        () => _error =
            'Complete the name and report heading, or the comment label and text.',
      );
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
      _notice = null;
    });
    try {
      if (design) {
        final body = AcademicReportTemplateWrite(
          id: _template?.id,
          revision: _template?.revision,
          name: _name.text,
          design: AcademicReportDesign(
            heading: _heading.text,
            introduction: _intro.text.isEmpty ? null : _intro.text,
            footer: _footer.text.isEmpty ? null : _footer.text,
            sections: _sections,
            showCrest: _crest,
          ),
        );
        final saved = await widget.repository.template(
          body,
          _keys.key('template', body.toJson()),
        );
        _keys.completed('template', body.toJson());
        if (!mounted) return;
        _templates = [..._templates.where((t) => t.id != saved.id), saved];
        _template = saved;
      } else {
        final body = AcademicReportCommentBankWrite(
          id: _bank?.id,
          revision: _bank?.revision,
          subjectId: _subject,
          scope: _subject == null ? 'homeroom' : 'subject',
          label: _bankLabel.text,
          text: _bankText.text,
          active: _active,
        );
        final saved = await widget.repository.bank(
          body,
          _keys.key('bank', body.toJson()),
        );
        _keys.completed('bank', body.toJson());
        if (!mounted) return;
        final bank = AcademicReportCommentBank(
          id: saved.id,
          revision: saved.revision,
          subjectId: _subject,
          scope: body.scope,
          label: body.label,
          text: body.text,
          active: _active,
        );
        _banks = [..._banks.where((b) => b.id != saved.id), bank];
        _bank = bank;
      }
      setState(
        () => _notice = design
            ? 'Report design saved. Existing batches keep their design.'
            : 'Comment bank saved.',
      );
    } catch (error) {
      if (mounted) setState(() => _error = reportFailure(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => ScreenScaffold(
    title: academicText(context, 'Report design and comment banks'),
    body: ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_busy) const LinearProgressIndicator(),
        if (_error != null)
          Text(
            _error!,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        if (_notice != null) Text(_notice!),
        AcademicText(
          'Report design',
          style: Theme.of(context).textTheme.headlineSmall,
        ),
        DropdownButtonFormField<int>(
          key: ValueKey('design:${_template?.id}'),
          initialValue: _template?.id ?? 0,
          isExpanded: true,
          decoration: InputDecoration(
            labelText: academicText(context, 'Design to edit'),
          ),
          items: [
            const DropdownMenuItem(value: 0, child: AcademicText('New design')),
            for (final t in _templates)
              DropdownMenuItem(value: t.id, child: Text(t.name)),
          ],
          onChanged: _busy
              ? null
              : (id) => _editTemplate(
                  _templates.where((t) => t.id == id).firstOrNull,
                ),
        ),
        TextField(
          controller: _name,
          enabled: !_busy,
          maxLength: 191,
          decoration: InputDecoration(
            labelText: academicText(context, 'Design name'),
          ),
        ),
        TextField(
          controller: _heading,
          enabled: !_busy,
          maxLength: 191,
          decoration: InputDecoration(
            labelText: academicText(context, 'Report heading'),
          ),
        ),
        TextField(
          controller: _intro,
          enabled: !_busy,
          maxLength: 3000,
          minLines: 2,
          maxLines: 5,
          decoration: InputDecoration(
            labelText: academicText(context, 'Introduction'),
          ),
        ),
        TextField(
          controller: _footer,
          enabled: !_busy,
          maxLength: 3000,
          minLines: 2,
          maxLines: 5,
          decoration: InputDecoration(
            labelText: academicText(context, 'Footer'),
          ),
        ),
        SwitchListTile(
          title: const AcademicText('Include school crest'),
          value: _crest,
          onChanged: _busy ? null : (value) => setState(() => _crest = value),
        ),
        const AcademicText(
          'Choose sections. Move enabled sections into their printed order.',
        ),
        for (final entry in reportSectionNames.entries)
          CheckboxListTile(
            title: Text(entry.value),
            value: _sections.contains(entry.key),
            onChanged: _busy || entry.key == 'subjects'
                ? null
                : (checked) => setState(() {
                    checked == true
                        ? _sections.add(entry.key)
                        : _sections.remove(entry.key);
                  }),
          ),
        for (var index = 0; index < _sections.length; index++)
          ListTile(
            title: Text(
              '${index + 1}. ${reportSectionNames[_sections[index]]}',
            ),
            trailing: Wrap(
              children: [
                IconButton(
                  tooltip: academicText(context, 'Move section up'),
                  onPressed: _busy || index == 0
                      ? null
                      : () => setState(() {
                          final value = _sections.removeAt(index);
                          _sections.insert(index - 1, value);
                        }),
                  icon: const Icon(Icons.arrow_upward),
                ),
                IconButton(
                  tooltip: academicText(context, 'Move section down'),
                  onPressed: _busy || index == _sections.length - 1
                      ? null
                      : () => setState(() {
                          final value = _sections.removeAt(index);
                          _sections.insert(index + 1, value);
                        }),
                  icon: const Icon(Icons.arrow_downward),
                ),
              ],
            ),
          ),
        FilledButton(
          onPressed: _busy ? null : () => _save(true),
          child: const AcademicText('Save design'),
        ),
        const SizedBox(height: 32),
        AcademicText(
          'Comment banks',
          style: Theme.of(context).textTheme.headlineSmall,
        ),
        const AcademicText(
          'Keep reusable comments generic. Do not put learner details in a bank.',
        ),
        DropdownButtonFormField<int>(
          key: ValueKey('bank:${_bank?.id}'),
          initialValue: _bank?.id ?? 0,
          isExpanded: true,
          decoration: InputDecoration(
            labelText: academicText(context, 'Comment bank to edit'),
          ),
          items: [
            const DropdownMenuItem(
              value: 0,
              child: AcademicText('New bank entry'),
            ),
            for (final b in _banks)
              DropdownMenuItem(value: b.id, child: Text(b.label)),
          ],
          onChanged: _busy
              ? null
              : (id) => _editBank(_banks.where((b) => b.id == id).firstOrNull),
        ),
        DropdownButtonFormField<int>(
          key: ValueKey('subject:$_subject'),
          initialValue: _subject ?? 0,
          isExpanded: true,
          decoration: InputDecoration(
            labelText: academicText(context, 'Comment scope'),
          ),
          items: [
            const DropdownMenuItem(value: 0, child: AcademicText('Homeroom')),
            for (final subject in widget.configuration.subjects)
              DropdownMenuItem(value: subject.id, child: Text(subject.name)),
          ],
          onChanged: _busy
              ? null
              : (id) => setState(() => _subject = id == 0 ? null : id),
        ),
        TextField(
          controller: _bankLabel,
          enabled: !_busy,
          maxLength: 191,
          decoration: InputDecoration(
            labelText: academicText(context, 'Bank label'),
          ),
        ),
        TextField(
          controller: _bankText,
          enabled: !_busy,
          maxLength: 3000,
          minLines: 3,
          maxLines: 6,
          decoration: InputDecoration(
            labelText: academicText(context, 'Reusable comment'),
          ),
        ),
        SwitchListTile(
          title: const AcademicText('Available for comments'),
          value: _active,
          onChanged: _busy ? null : (value) => setState(() => _active = value),
        ),
        FilledButton(
          onPressed: _busy ? null : () => _save(false),
          child: const AcademicText('Save bank entry'),
        ),
      ],
    ),
  );
}
