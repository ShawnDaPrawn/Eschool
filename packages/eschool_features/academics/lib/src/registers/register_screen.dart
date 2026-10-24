import 'family_attendance_screen.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'register_cubit.dart';
import 'register_repository.dart';
import 'register_store.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});
  static Widget withRepository(
    RegisterRepository repository,
    RegisterStore store,
    int register, {
    AcademicRegisterMatrixData? initial,
  }) => BlocProvider(
    create: (_) =>
        RegisterCubit(repository, store, register)..load(initial: initial),
    child: const RegisterScreen(),
  );
  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen>
    with WidgetsBindingObserver {
  final _qr = TextEditingController(), _reason = TextEditingController();
  bool _reasonLoaded = false;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      context.read<RegisterCubit>().flush();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _qr.dispose();
    _reason.dispose();
    super.dispose();
  }

  Future<void> _camera() async {
    final cubit = context.read<RegisterCubit>();
    // Native scanning works without fetching executable code from a CDN.
    if (kIsWeb) {
      MobileScannerPlatform.instance.setWebBarcodeReader(
        WebBarcodeReader.barcodeDetector,
      );
    }
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (context) => SafeArea(
        child: SizedBox(
          height: MediaQuery.sizeOf(context).height * .85,
          child: Column(
            children: [
              ListTile(
                title: const Text('Scan learner QR codes'),
                trailing: IconButton(
                  tooltip: 'Done scanning',
                  onPressed: () => Navigator.of(context).pop(),
                  icon: const Icon(Icons.close),
                ),
              ),
              const Padding(
                padding: EdgeInsets.all(12),
                child: Text(
                  'Scan each learner once. The dated roster decides who can be marked.',
                ),
              ),
              Expanded(
                child: MobileScanner(
                  onDetect: (capture) {
                    for (final code in capture.barcodes) {
                      final value = code.rawValue;
                      if (value?.startsWith('NEAR1.') == true) {
                        cubit.scan(value!);
                      }
                    }
                  },
                  errorBuilder: (_, _) => const Center(
                    child: Padding(
                      padding: EdgeInsets.all(24),
                      child: Text(
                        'Camera scanning is unavailable here. Use the QR input or tap the register.',
                      ),
                    ),
                  ),
                ),
              ),
              BlocBuilder<RegisterCubit, RegisterState>(
                bloc: cubit,
                builder: (_, state) => Padding(
                  padding: const EdgeInsets.all(16),
                  child: Text(state.notice ?? 'Ready to scan'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _review() async {
    final cubit = context.read<RegisterCubit>();
    if (cubit.state.review == null) await cubit.reviewConflict();
    if (!mounted || cubit.isClosed || cubit.state.review == null) return;
    final current = cubit.state.review!;
    final wanted = cubit.intended;
    final differences = current.learners
        .where(
          (row) =>
              wanted[row.learnerId] != null &&
              wanted[row.learnerId] != row.state,
        )
        .toList();
    final selected = differences.map((row) => row.learnerId).toSet();
    final missing = wanted.keys
        .where((id) => !current.learners.any((row) => row.learnerId == id))
        .length;
    final result = await showDialog<Set<int>>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (_, setDialog) => AlertDialog(
          title: const Text('Review saved changes'),
          content: SizedBox(
            width: 600,
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Compare the current register with your saved changes. Selected changes become a new draft; saving still checks current revisions.',
                  ),
                  if (missing > 0)
                    Text(
                      '$missing queued learner entries are no longer on this roster and cannot be applied.',
                    ),
                  if (!current.canWrite)
                    const Text(
                      'Ask the grade head to record corrections for this day. Your saved queue is retained.',
                    ),
                  if (differences.isEmpty)
                    const Text(
                      'Your saved values already match the current register.',
                    ),
                  for (final row in differences)
                    CheckboxListTile(
                      value: selected.contains(row.learnerId),
                      title: Text(row.name),
                      subtitle: Text(
                        'Current: ${row.state} · Saved: ${wanted[row.learnerId]}',
                      ),
                      onChanged: !current.canWrite
                          ? null
                          : (value) => setDialog(() {
                              if (value == true) {
                                selected.add(row.learnerId);
                              } else {
                                selected.remove(row.learnerId);
                              }
                            }),
                    ),
                ],
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(),
              child: const Text('Keep queue'),
            ),
            FilledButton(
              onPressed: !current.canWrite || selected.isEmpty
                  ? null
                  : () => Navigator.of(dialogContext).pop(selected),
              child: const Text('Use reviewed changes'),
            ),
          ],
        ),
      ),
    );
    if (result != null && !cubit.isClosed) {
      await cubit.useReviewedChanges(result);
    }
  }

  Future<void> _discard() async {
    final cubit = context.read<RegisterCubit>();
    final discard = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Discard saved changes?'),
        content: const Text(
          'This removes the queued changes on this device after loading the current register. It does not undo attendance already saved at the school.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Keep changes'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Discard changes'),
          ),
        ],
      ),
    );
    if (discard == true && !cubit.isClosed) await cubit.discardQueued();
  }

  Widget _statusButton(
    RegisterCubit cubit,
    RegisterRowView row,
    String value,
    IconData icon,
    bool enabled,
  ) => IconButton(
    tooltip: '${row.learner.name}: $value',
    isSelected: row.state == value,
    selectedIcon: Icon(icon, color: Theme.of(context).colorScheme.primary),
    icon: Icon(icon),
    onPressed: enabled ? () => cubit.set(row.learner.learnerId, value) : null,
  );
  Widget _moreStates(
    RegisterState state,
    RegisterCubit cubit,
    RegisterRowView row,
  ) => PopupMenuButton<String>(
    tooltip: 'More attendance states for ${row.learner.name}',
    enabled: state.canEdit,
    onSelected: (value) => cubit.set(row.learner.learnerId, value),
    itemBuilder: (_) => [
      const PopupMenuItem(value: 'late', child: Text('Late')),
      const PopupMenuItem(value: 'excused', child: Text('Excused')),
      if (state.matrix?.timetableLessonId == null) ...const [
        PopupMenuItem(value: 'first_half', child: Text('First half absent')),
        PopupMenuItem(value: 'second_half', child: Text('Second half absent')),
        PopupMenuItem(value: 'holiday', child: Text('Holiday')),
      ],
    ],
  );
  Widget _list(RegisterState state, RegisterCubit cubit) => LayoutBuilder(
    builder: (context, box) {
      final rows = state.rows;
      if (rows.isEmpty) {
        return const Center(
          child: Text(
            'No learners are enrolled in this register on this date.',
          ),
        );
      }
      if (box.maxWidth / MediaQuery.textScalerOf(context).scale(1) >= 800) {
        return SingleChildScrollView(
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: DataTable(
              dataRowMinHeight: 64,
              dataRowMaxHeight: double.infinity,
              columns: const [
                DataColumn(label: Text('Learner')),
                DataColumn(label: Text('Present')),
                DataColumn(label: Text('Absent')),
                DataColumn(label: Text('Late')),
                DataColumn(label: Text('Excused')),
              ],
              rows: [
                for (final row in rows)
                  DataRow(
                    cells: [
                      DataCell(
                        SizedBox(
                          width: 250,
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(row.learner.name),
                              Text(
                                attendanceStateLabel(row.state),
                                style: Theme.of(context).textTheme.labelMedium,
                              ),
                            ],
                          ),
                        ),
                      ),
                      DataCell(
                        _statusButton(
                          cubit,
                          row,
                          'present',
                          Icons.check_circle_outline,
                          state.canEdit,
                        ),
                      ),
                      DataCell(
                        _statusButton(
                          cubit,
                          row,
                          'absent',
                          Icons.cancel_outlined,
                          state.canEdit,
                        ),
                      ),
                      DataCell(
                        _statusButton(
                          cubit,
                          row,
                          'late',
                          Icons.schedule,
                          state.canEdit,
                        ),
                      ),
                      DataCell(
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            _statusButton(
                              cubit,
                              row,
                              'excused',
                              Icons.event_available_outlined,
                              state.canEdit,
                            ),
                            _moreStates(state, cubit, row),
                          ],
                        ),
                      ),
                    ],
                  ),
              ],
            ),
          ),
        );
      }
      return ListView.builder(
        itemCount: rows.length,
        itemBuilder: (context, index) {
          final row = rows[index];
          return Card(
            child: Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    row.learner.name,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  Row(
                    children: [
                      Expanded(child: Text(attendanceStateLabel(row.state))),
                      _statusButton(
                        cubit,
                        row,
                        'present',
                        Icons.check_circle_outline,
                        state.canEdit,
                      ),
                      _statusButton(
                        cubit,
                        row,
                        'absent',
                        Icons.cancel_outlined,
                        state.canEdit,
                      ),
                      _moreStates(state, cubit, row),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      );
    },
  );
  @override
  Widget build(
    BuildContext context,
  ) => BlocBuilder<RegisterCubit, RegisterState>(
    builder: (context, state) {
      final cubit = context.read<RegisterCubit>(), matrix = state.matrix;
      if (!_reasonLoaded && matrix != null) {
        _reason.text = state.reason;
        _reasonLoaded = true;
      }
      final counts = <String, int>{};
      for (final row in state.rows) {
        counts[row.state] = (counts[row.state] ?? 0) + 1;
      }
      final body = ScreenScaffold(
        title: matrix == null
            ? 'Academic register'
            : '${matrix.label} · ${matrix.onDate}',
        body: state.loading
            ? const Center(child: CircularProgressIndicator())
            : matrix == null
            ? Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(state.error ?? 'Register unavailable'),
                    TextButton(
                      onPressed: cubit.load,
                      child: const Text('Try again'),
                    ),
                  ],
                ),
              )
            : LayoutBuilder(
                builder: (context, box) => Column(
                  children: [
                    if (state.busy) const LinearProgressIndicator(),
                    ConstrainedBox(
                      constraints: BoxConstraints(
                        maxHeight: box.maxHeight * .55,
                      ),
                      child: SingleChildScrollView(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Wrap(
                                spacing: 8,
                                runSpacing: 4,
                                children: [
                                  Chip(
                                    label: Text(
                                      '${counts['present'] ?? 0} present',
                                    ),
                                  ),
                                  Chip(
                                    label: Text(
                                      '${counts['absent'] ?? 0} absent',
                                    ),
                                  ),
                                  Chip(
                                    label: Text(
                                      '${counts['unmarked'] ?? 0} unmarked',
                                    ),
                                  ),
                                  if (state.queue.isNotEmpty)
                                    Chip(
                                      label: Text(
                                        '${state.queue.length} saved operations pending sync',
                                      ),
                                    ),
                                  if (state.draft.isNotEmpty)
                                    const Chip(label: Text('Unsaved draft')),
                                  if (state.offline)
                                    const Chip(label: Text('Offline copy')),
                                ],
                              ),
                              if (state.error != null)
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 8,
                                  ),
                                  child: Text(
                                    state.error!,
                                    style: TextStyle(
                                      color: Theme.of(
                                        context,
                                      ).colorScheme.error,
                                    ),
                                  ),
                                ),
                              if (state.notice != null)
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 4,
                                  ),
                                  child: Text(state.notice!),
                                ),
                              if (!matrix.canWrite)
                                const Text(
                                  'This day is read-only. Ask the grade head for corrections.',
                                ),
                              Wrap(
                                spacing: 8,
                                runSpacing: 8,
                                crossAxisAlignment: WrapCrossAlignment.center,
                                children: [
                                  FilledButton.icon(
                                    onPressed:
                                        !state.canEdit || state.draft.isEmpty
                                        ? null
                                        : cubit.save,
                                    icon: const Icon(Icons.save_outlined),
                                    label: const Text('Save register'),
                                  ),
                                  OutlinedButton(
                                    onPressed: state.canEdit
                                        ? cubit.remainingPresent
                                        : null,
                                    child: const Text('Mark remaining present'),
                                  ),
                                  OutlinedButton.icon(
                                    onPressed: state.canEdit ? _camera : null,
                                    icon: const Icon(Icons.qr_code_scanner),
                                    label: const Text('Scan QR'),
                                  ),
                                  if (state.queue.isNotEmpty &&
                                      !state.conflict &&
                                      !state.blocked)
                                    TextButton(
                                      onPressed: state.busy
                                          ? null
                                          : cubit.flush,
                                      child: const Text('Sync saved queue'),
                                    ),
                                  if (state.conflict || state.blocked)
                                    TextButton(
                                      onPressed: state.busy ? null : _review,
                                      child: const Text('Review saved changes'),
                                    ),
                                  if (state.conflict || state.blocked)
                                    TextButton(
                                      onPressed: state.busy ? null : _discard,
                                      child: const Text(
                                        'Discard saved changes',
                                      ),
                                    ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              LayoutBuilder(
                                builder: (context, box) {
                                  final fields = [
                                    TextField(
                                      key: const ValueKey('register-qr-input'),
                                      controller: _qr,
                                      enabled: state.canEdit,
                                      decoration: const InputDecoration(
                                        labelText: 'QR input',
                                        helperText:
                                            'Scan with a QR keyboard reader or paste the learner code',
                                      ),
                                      onSubmitted: (value) {
                                        cubit.scan(value);
                                        _qr.clear();
                                      },
                                    ),
                                    TextField(
                                      key: const ValueKey(
                                        'register-correction-reason',
                                      ),
                                      controller: _reason,
                                      enabled: state.canEdit,
                                      maxLength: 500,
                                      decoration: const InputDecoration(
                                        labelText: 'Correction reason',
                                        helperText:
                                            'Required when changing an already captured entry',
                                      ),
                                      onChanged: cubit.reason,
                                    ),
                                  ];
                                  if (box.maxWidth < 700) {
                                    return Column(
                                      children: [
                                        fields[0],
                                        const SizedBox(height: 12),
                                        fields[1],
                                      ],
                                    );
                                  }
                                  return Row(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Expanded(child: fields[0]),
                                      const SizedBox(width: 16),
                                      Expanded(child: fields[1]),
                                    ],
                                  );
                                },
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    Expanded(child: _list(state, cubit)),
                  ],
                ),
              ),
      );
      return AdaptiveScaffold(
        body: body,
        destinations: const [
          AdaptiveDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Home',
          ),
          AdaptiveDestination(
            icon: Icon(Icons.fact_check_outlined),
            selectedIcon: Icon(Icons.fact_check),
            label: 'Register',
          ),
        ],
        selectedIndex: 1,
        onDestinationSelected: (index) {
          if (index == 0) Navigator.of(context).pop();
        },
      );
    },
  );
}
