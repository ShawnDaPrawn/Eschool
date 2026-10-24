import '../localization/academic_text.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'report_family_cubit.dart';
import 'report_repository.dart';

class ReportPdfScreen extends StatefulWidget {
  const ReportPdfScreen({
    required this.repository,
    required this.card,
    required this.title,
    this.learner,
    this.batch,
    super.key,
  });
  final ReportRepository repository;
  final int card;
  final int? learner, batch;
  final String title;
  @override
  State<ReportPdfScreen> createState() => _ReportPdfScreenState();
}

class _ReportPdfScreenState extends State<ReportPdfScreen>
    with WidgetsBindingObserver {
  late ReportPdfCubit _cubit;
  bool _saving = false, _pdfReady = false;
  void _create() {
    _cubit = ReportPdfCubit(
      widget.repository,
      card: widget.card,
      learner: widget.learner,
      batch: widget.batch,
    )..load();
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _create();
  }

  @override
  void didUpdateWidget(covariant ReportPdfScreen old) {
    super.didUpdateWidget(old);
    if (old.repository != widget.repository ||
        old.card != widget.card ||
        old.learner != widget.learner ||
        old.batch != widget.batch) {
      _cubit.close();
      _create();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _cubit.load();
    } else if (state == AppLifecycleState.hidden ||
        state == AppLifecycleState.paused) {
      _cubit.clear();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _cubit.close();
    super.dispose();
  }

  Future<void> _save() async {
    if (_cubit.state.bytes == null || _saving) return;
    setState(() => _saving = true);
    try {
      await _cubit.load();
      if (!mounted || _cubit.state.bytes == null) return;
      final bytes = _cubit.state.bytes!;
      await DeviceFiles.saveDocument(
        bytes,
        'reports/report-${widget.card}.pdf',
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: AcademicText('A report copy was saved to this device.'),
          ),
        );
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: AcademicText('Could not save the report copy.'),
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(
    BuildContext context,
  ) => BlocConsumer<ReportPdfCubit, ReportPdfState>(
    bloc: _cubit,
    listener: (_, state) {
      if (state.busy || state.bytes == null) setState(() => _pdfReady = false);
    },
    builder: (context, state) => ScreenScaffold(
      title: widget.title,
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: Wrap(
              spacing: 12,
              runSpacing: 8,
              children: [
                OutlinedButton.icon(
                  onPressed: state.busy ? null : _cubit.load,
                  icon: const Icon(Icons.refresh),
                  label: const AcademicText('Refresh access'),
                ),
                OutlinedButton.icon(
                  onPressed: state.bytes == null || !_pdfReady || _saving
                      ? null
                      : _save,
                  icon: const Icon(Icons.download_outlined),
                  label: const AcademicText('Save a copy'),
                ),
                if (_pdfReady) const AcademicText('Report preview ready'),
                const AcademicText(
                  'Saved copies remain on your device. Keep them private.',
                ),
              ],
            ),
          ),
          Expanded(
            child: state.busy
                ? const Center(child: CircularProgressIndicator())
                : state.bytes != null
                ? PdfDocumentView(
                    key: ValueKey(state.bytes),
                    bytes: state.bytes,
                    onLoaded: () {
                      if (mounted) setState(() => _pdfReady = true);
                    },
                    onError: () {
                      if (mounted) _cubit.clear();
                    },
                  )
                : Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            state.error ?? 'Refresh access to open the report.',
                          ),
                          TextButton(
                            onPressed: _cubit.load,
                            child: const AcademicText('Try again'),
                          ),
                        ],
                      ),
                    ),
                  ),
          ),
        ],
      ),
    ),
  );
}
