import 'dart:math' as math;
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'academic_labels.dart';
import 'mark_draft.dart';
import 'mark_grid_cubit.dart';
import 'mark_input.dart';
import 'marks_repository.dart';
import 'score_cell.dart';
import 'mark_release_dialog.dart';

class MarkCaptureScreen extends StatefulWidget {
  const MarkCaptureScreen({required this.title, super.key});
  final String title;
  static Widget withRepository(
    MarksRepository repository, {
    required int group,
    required int term,
    required String title,
  }) => BlocProvider(
    create: (_) => MarkGridCubit(repository)..load(group, term),
    child: MarkCaptureScreen(title: title),
  );
  @override
  State<MarkCaptureScreen> createState() => _MarkCaptureScreenState();
}

class _MarkCaptureScreenState extends State<MarkCaptureScreen> {
  final _selected = <int>{};
  final _focus = <(int, int), FocusNode>{};
  final _rowKeys = <int, GlobalKey>{};
  final _rows = ScrollController();
  int _activeRow = 0, _activeColumn = 0;
  String? _context;
  String l(String key) => academicLabel(context, key);
  MarkGridCubit get cubit => context.read<MarkGridCubit>();
  @override
  void dispose() {
    _rows.dispose();
    super.dispose();
  }

  Future<bool> _confirmDiscard() async {
    if (!cubit.state.dirty) return true;
    return await showDialog<bool>(
          context: context,
          builder: (context) => AlertDialog(
            title: Text(l('discardTitle')),
            content: Text(l('discardBody')),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: Text(l('keep')),
              ),
              FilledButton(
                onPressed: () => Navigator.pop(context, true),
                child: Text(l('useLatest')),
              ),
            ],
          ),
        ) ??
        false;
  }

  Future<void> _leave() async {
    if (cubit.state.busy || !await _confirmDiscard() || !mounted) return;
    Navigator.of(context).pop();
  }

  Future<void> _reload({int? page, int? task}) async {
    if (cubit.state.busy || !await _confirmDiscard() || !mounted) return;
    final state = cubit.state;
    await cubit.load(
      state.group,
      state.term,
      page: page ?? state.matrix?.currentPage ?? 1,
      task: task ?? state.task?.id,
    );
  }

  Future<void> _paste() async {
    final scope = _context, row = _activeRow, column = _activeColumn;
    final data = await Clipboard.getData(Clipboard.kTextPlain);
    if (mounted && scope == _context && data?.text != null) {
      cubit.paste(data!.text!, row: row, column: column);
    }
  }

  Future<void> _enter(int row, int column) async {
    final scope = _context;
    if (row + 1 >= cubit.state.matrix!.learners.length) return;
    // Phone cards have variable heights; reveal the actual focus context.
    for (var attempt = 0; attempt < 8; attempt++) {
      if (!mounted || scope != _context) return;
      final target = _focus[(row + 1, column)];
      if (target?.context != null) {
        await Scrollable.ensureVisible(target!.context!, alignment: 0.5);
        if (mounted && scope == _context && target.context != null) {
          target.requestFocus();
        }
        return;
      }
      if (!_rows.hasClients) return;
      final position = _rows.position;
      final next = math.min(
        position.pixels + position.viewportDimension * 0.6,
        position.maxScrollExtent,
      );
      if (next == position.pixels) return;
      _rows.jumpTo(next);
      await WidgetsBinding.instance.endOfFrame;
    }
  }

  Future<void> _details(AcademicMatrixLearner learner) async {
    var row = cubit.draft(learner);
    final enabled = cubit.canEdit(learner);
    final note = TextEditingController(text: row.note);
    final next = await showDialog<MarkDraft>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, update) => AlertDialog(
          title: Text(learner.displayName),
          content: SizedBox(
            width: 440,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                DropdownButtonFormField<AcademicMarkRowDispositionEnum>(
                  initialValue: row.disposition,
                  isExpanded: true,
                  decoration: InputDecoration(labelText: l('details')),
                  items: AcademicMarkRowDispositionEnum.values
                      .where(
                        (v) =>
                            v !=
                            AcademicMarkRowDispositionEnum
                                .unknownDefaultOpenApi,
                      )
                      .map(
                        (v) =>
                            DropdownMenuItem(value: v, child: Text(l(v.value))),
                      )
                      .toList(),
                  onChanged: !enabled
                      ? null
                      : (value) {
                          if (value != null) {
                            update(() => row = row.change(disposition: value));
                          }
                        },
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: note,
                  readOnly: !enabled,
                  maxLength: 500,
                  minLines: 2,
                  maxLines: 4,
                  decoration: InputDecoration(labelText: l('reason')),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(l('cancel')),
            ),
            if (enabled)
              FilledButton(
                onPressed: () =>
                    Navigator.pop(context, row.change(note: note.text)),
                child: Text(l('apply')),
              ),
          ],
        ),
      ),
    );
    if (mounted && next != null) {
      cubit.change(learner, disposition: next.disposition, note: next.note);
    }
    // Dialog controls finish unmounting before disposing their controller.
    WidgetsBinding.instance.addPostFrameCallback((_) => note.dispose());
  }

  Future<void> _compare() async {
    final before = cubit.state;
    try {
      final latest = await cubit.repository.matrix(
        before.group,
        before.term,
        page: before.matrix!.currentPage,
      );
      if (!mounted ||
          cubit.state.group != before.group ||
          cubit.state.term != before.term) {
        return;
      }
      final latestTask = latest.tasks
          .where((task) => task.canonicalTaskId == before.task!.canonicalTaskId)
          .firstOrNull;
      final accepted = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(l('compare')),
          content: SizedBox(
            width: 600,
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  for (final learner in latest.learners.where(
                    (l) => before.drafts.containsKey(l.learnerId),
                  ))
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      child: Text(
                        '${learner.displayName}\n${l('yourEntries')}: ${before.drafts[learner.learnerId]!.values.values.join(' / ')}\n${l('latest')}: ${latestTask == null ? l('taskChanged') : MarkDraft.saved(learner, latestTask).values.values.join(' / ')}',
                      ),
                    ),
                ],
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(l('keep')),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(l('useLatest')),
            ),
          ],
        ),
      );
      if (accepted == true && mounted) {
        await cubit.load(
          before.group,
          before.term,
          page: latest.currentPage,
          task: latestTask?.id,
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e is ApiException && e.isApiMessage
                  ? e.errorMessage
                  : l('loadFailed'),
            ),
          ),
        );
      }
    }
  }

  Widget _running(AcademicMatrixLearner learner, MarkGridState state) {
    final result = state.previews[learner.learnerId] ?? learner.result;
    return Text(
      '${result.runningBp == null ? '—' : '${markDecimal(result.runningBp!)}%'}${result.provisional ? ' · ${l('provisional')}' : ''}\n${l('rating')}: ${result.runningRatingLevel ?? '—'} · ${l('coverage')}: ${markDecimal(result.coverageBp)}%${state.previewPending && state.drafts.containsKey(learner.learnerId) ? '\n${l('updating')}' : ''}',
      style: Theme.of(context).textTheme.bodySmall,
    );
  }

  Widget _cell(
    AcademicMatrixLearner learner,
    int r,
    int c,
    MarkGridState state,
  ) {
    final task = state.task!;
    final question = task.questions.isEmpty ? null : task.questions[c];
    final field = question?.id ?? 0;
    final value = cubit.draft(learner).values[field] ?? '';
    if (!cubit.canEdit(learner) || cubit.draft(learner).noScore) {
      return Padding(
        padding: const EdgeInsets.all(12),
        child: Semantics(
          label:
              '${learner.displayName}, ${l('question')} ${question?.number ?? ''}',
          child: Text(value.isEmpty ? '—' : value),
        ),
      );
    }
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 6),
      child: FocusTraversalOrder(
        order: NumericFocusOrder(
          (r * math.max(1, task.questions.length) + c).toDouble(),
        ),
        child: ScoreCell(
          key: ValueKey(
            '${state.group}:${state.term}:${task.id}:${learner.learnerId}:$field',
          ),
          value: value,
          maximum: question?.maxMark ?? task.maxMark,
          label:
              '${learner.displayName} · ${question == null ? task.name : '${l('question')} ${question.number}'} / ${question?.maxMark ?? task.maxMark}',
          onChanged: (text) => cubit.change(learner, field: field, value: text),
          onPaste: (text) => cubit.paste(text, row: r, column: c),
          onEnter: () => _enter(r, c),
          onFocused: () {
            _activeRow = r;
            _activeColumn = c;
          },
          register: (node, adding) {
            if (!adding) {
              if (identical(_focus[(r, c)], node)) _focus.remove((r, c));
            } else {
              _focus[(r, c)] = node;
            }
          },
        ),
      ),
    );
  }

  Widget _select(AcademicMatrixLearner learner, MarkGridState state) =>
      state.matrix!.capabilities.release || state.matrix!.capabilities.moderate
      ? Checkbox(
          value: _selected.contains(learner.learnerId),
          semanticLabel: '${l('select')}: ${learner.displayName}',
          onChanged: state.busy
              ? null
              : (value) => setState(() {
                  if (value == true) {
                    _selected.add(learner.learnerId);
                  } else {
                    _selected.remove(learner.learnerId);
                  }
                }),
        )
      : const SizedBox.shrink();
  Widget _phone(MarkGridState state) => ListView.builder(
    controller: _rows,
    padding: const EdgeInsets.all(12),
    itemCount: state.matrix!.learners.length,
    itemBuilder: (context, r) {
      final learner = state.matrix!.learners[r];
      final columns = math.max(1, state.task!.questions.length);
      return Card(
        key: _rowKeys.putIfAbsent(r, GlobalKey.new),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  _select(learner, state),
                  Expanded(
                    child: Text(
                      learner.displayName,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                  ),
                  IconButton(
                    tooltip: l('details'),
                    onPressed: () => _details(learner),
                    icon: const Icon(Icons.more_horiz),
                  ),
                ],
              ),
              _running(learner, state),
              const SizedBox(height: 8),
              LayoutBuilder(
                builder: (context, box) => Wrap(
                  children: [
                    for (var c = 0; c < columns; c++)
                      SizedBox(
                        width: box.maxWidth / (box.maxWidth < 300 ? 1 : 2),
                        child: _cell(learner, r, c, state),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
    },
  );
  Widget _desktop(MarkGridState state) {
    final columns = math.max(1, state.task!.questions.length);
    final width = 260.0 + columns * 132 + 240;
    return LayoutBuilder(
      builder: (context, box) => Scrollbar(
        notificationPredicate: (n) => n.metrics.axis == Axis.horizontal,
        child: SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: SizedBox(
            width: math.max(width, box.maxWidth),
            height: box.maxHeight,
            child: Column(
              children: [
                SizedBox(
                  height: math.max(
                    50,
                    MediaQuery.textScalerOf(context).scale(14) * 4,
                  ),
                  child: Row(
                    children: [
                      SizedBox(
                        width: 260,
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Text(l('learner')),
                        ),
                      ),
                      for (var c = 0; c < columns; c++)
                        SizedBox(
                          width: 132,
                          child: Text(
                            state.task!.questions.isEmpty
                                ? '${l('marks')} / ${state.task!.maxMark}'
                                : '${l('question')} ${state.task!.questions[c].number}\n/ ${state.task!.questions[c].maxMark}',
                          ),
                        ),
                      Expanded(child: Text(l('running'))),
                    ],
                  ),
                ),
                const Divider(height: 1),
                Expanded(
                  child: ListView.builder(
                    controller: _rows,
                    itemExtent: math.max(
                      76,
                      MediaQuery.textScalerOf(context).scale(14) * 6,
                    ),
                    itemCount: state.matrix!.learners.length,
                    itemBuilder: (context, r) {
                      final learner = state.matrix!.learners[r];
                      return Row(
                        children: [
                          SizedBox(
                            width: 260,
                            child: Row(
                              children: [
                                _select(learner, state),
                                Expanded(
                                  child: Padding(
                                    padding: const EdgeInsets.all(8),
                                    child: Text(learner.displayName),
                                  ),
                                ),
                                IconButton(
                                  tooltip: l('details'),
                                  onPressed: () => _details(learner),
                                  icon: const Icon(Icons.more_horiz),
                                ),
                              ],
                            ),
                          ),
                          for (var c = 0; c < columns; c++)
                            SizedBox(
                              width: 132,
                              child: _cell(learner, r, c, state),
                            ),
                          Expanded(
                            child: Padding(
                              padding: const EdgeInsets.all(8),
                              child: _running(learner, state),
                            ),
                          ),
                        ],
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(
    BuildContext context,
  ) => BlocBuilder<MarkGridCubit, MarkGridState>(
    builder: (context, state) {
      final key =
          '${state.group}:${state.term}:${state.task?.id}:${state.matrix?.currentPage}';
      if (_context != key) {
        _context = key;
        _selected.clear();
        _rowKeys.clear();
        _activeRow = _activeColumn = 0;
      }
      final matrix = state.matrix;
      final body = ScreenScaffold(
        title: widget.title,
        body: state.loading
            ? const Center(child: CircularProgressIndicator())
            : matrix == null
            ? Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      state.error?.isApiMessage == true
                          ? state.error!.errorMessage
                          : l('loadFailed'),
                    ),
                    TextButton(
                      onPressed: () => _reload(),
                      child: Text(l('retry')),
                    ),
                  ],
                ),
              )
            : Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      children: [
                        DropdownButtonFormField<int>(
                          initialValue: state.task?.id,
                          isExpanded: true,
                          decoration: InputDecoration(labelText: l('task')),
                          items: matrix.tasks
                              .map(
                                (t) => DropdownMenuItem(
                                  value: t.id,
                                  child: Text(
                                    t.name,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              )
                              .toList(),
                          onChanged: state.busy
                              ? null
                              : (id) {
                                  if (id != null) _reload(task: id);
                                },
                        ),
                        if (matrix.locked) Text(l('locked')),
                        if (state.error != null)
                          Padding(
                            padding: const EdgeInsets.all(8),
                            child: Text(
                              state.error!.isApiMessage
                                  ? state.error!.errorMessage
                                  : l('loadFailed'),
                              style: TextStyle(
                                color: Theme.of(context).colorScheme.error,
                              ),
                            ),
                          ),
                        if (state.conflict)
                          Wrap(
                            crossAxisAlignment: WrapCrossAlignment.center,
                            children: [
                              Text(l('conflict')),
                              TextButton(
                                onPressed: _compare,
                                child: Text(l('compare')),
                              ),
                            ],
                          ),
                        Wrap(
                          spacing: 8,
                          crossAxisAlignment: WrapCrossAlignment.center,
                          children: [
                            if (matrix.capabilities.capture ||
                                matrix.capabilities.correct)
                              OutlinedButton.icon(
                                onPressed:
                                    state.busy ||
                                        matrix.locked ||
                                        state.conflict
                                    ? null
                                    : _paste,
                                icon: const Icon(Icons.content_paste),
                                label: Text(l('paste')),
                              ),
                            if (matrix.capabilities.correct)
                              FilterChip(
                                label: Text(l('correction')),
                                selected: state.correction,
                                onSelected: state.busy
                                    ? null
                                    : cubit.correction,
                              ),
                            if (matrix.capabilities.moderate)
                              OutlinedButton(
                                onPressed:
                                    state.busy ||
                                        state.dirty ||
                                        _selected.isEmpty ||
                                        matrix.locked
                                    ? null
                                    : _moderate,
                                child: Text(l('moderate')),
                              ),
                            if (matrix.capabilities.release)
                              OutlinedButton(
                                onPressed:
                                    state.busy ||
                                        state.dirty ||
                                        _selected.isEmpty
                                    ? null
                                    : _release,
                                child: Text(l('release')),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  Expanded(
                    child: state.task == null
                        ? Center(child: Text(l('noTasks')))
                        : matrix.learners.isEmpty
                        ? Center(child: Text(l('noLearners')))
                        : FocusTraversalGroup(
                            policy: OrderedTraversalPolicy(),
                            child: MediaQuery.sizeOf(context).width < 700
                                ? _phone(state)
                                : _desktop(state),
                          ),
                  ),
                  SafeArea(
                    top: false,
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Wrap(
                        spacing: 12,
                        runSpacing: 8,
                        crossAxisAlignment: WrapCrossAlignment.center,
                        children: [
                          FilledButton.icon(
                            onPressed:
                                !state.dirty ||
                                    state.busy ||
                                    state.conflict ||
                                    matrix.locked
                                ? null
                                : _save,
                            icon: const Icon(Icons.save_outlined),
                            label: Text(l('save')),
                          ),
                          TextButton(
                            onPressed: state.busy ? null : () => _reload(),
                            child: Text(l('reload')),
                          ),
                          if (matrix.lastPage > 1) ...[
                            IconButton(
                              tooltip: l('previous'),
                              onPressed: state.busy || matrix.currentPage <= 1
                                  ? null
                                  : () => _reload(page: matrix.currentPage - 1),
                              icon: const Icon(Icons.chevron_left),
                            ),
                            Text('${matrix.currentPage} / ${matrix.lastPage}'),
                            IconButton(
                              tooltip: l('next'),
                              onPressed:
                                  state.busy ||
                                      matrix.currentPage >= matrix.lastPage
                                  ? null
                                  : () => _reload(page: matrix.currentPage + 1),
                              icon: const Icon(Icons.chevron_right),
                            ),
                          ],
                          if (state.dirty) Text(l('pending')),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
      );
      return PopScope(
        canPop: !state.dirty && !state.busy,
        onPopInvokedWithResult: (didPop, result) {
          if (!didPop) _leave();
        },
        child: AdaptiveScaffold(
          body: body,
          destinations: [
            AdaptiveDestination(
              icon: const Icon(Icons.groups_outlined),
              selectedIcon: const Icon(Icons.groups),
              label: l('groups'),
            ),
            AdaptiveDestination(
              icon: const Icon(Icons.grid_on_outlined),
              selectedIcon: const Icon(Icons.grid_on),
              label: l('marks'),
            ),
          ],
          selectedIndex: 1,
          onDestinationSelected: (index) {
            if (index == 0) _leave();
          },
        ),
      );
    },
  );

  Future<void> _save() async {
    final saved = await cubit.save();
    if (mounted && saved) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(l('saved'))));
    }
  }

  Future<void> _moderate() async {
    final reason = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l('moderate')),
        content: TextField(
          controller: reason,
          maxLength: 500,
          decoration: InputDecoration(labelText: l('reason')),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(l('cancel')),
          ),
          FilledButton(
            onPressed: () {
              if (reason.text.trim().isNotEmpty) Navigator.pop(context, true);
            },
            child: Text(l('moderate')),
          ),
        ],
      ),
    );
    if (mounted && confirmed == true) {
      await cubit.moderate(Set.of(_selected), reason.text);
    }
    WidgetsBinding.instance.addPostFrameCallback((_) => reason.dispose());
  }

  Future<void> _release() async {
    final state = cubit.state;
    final learners = {
      for (final learner in state.matrix!.learners.where(
        (l) => _selected.contains(l.learnerId),
      ))
        learner.learnerId: learner.displayName,
    };
    final released = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (context) => MarkReleaseDialog(
        repository: cubit.repository,
        group: state.group,
        term: state.term,
        learners: learners,
      ),
    );
    if (mounted && released == true) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(l('released'))));
    }
  }
}
