import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:flutter/material.dart';
import '../localization/academic_text.dart';
import 'recognition_repository.dart';

/// A current-year home summary. Socket references only trigger protected HTTP
/// reads; celebration wording never comes from a socket payload or disk cache.
class RecognitionHomeSummary extends StatefulWidget {
  const RecognitionHomeSummary({
    required this.repository,
    required this.learnerId,
    super.key,
  });
  final RecognitionRepository repository;
  final int learnerId;
  @override
  State<RecognitionHomeSummary> createState() => _RecognitionHomeSummaryState();
}

class _RecognitionHomeSummaryState extends State<RecognitionHomeSummary>
    with WidgetsBindingObserver {
  RecognitionHouseCounters? _counters;
  StreamSubscription<RecognitionInvalidation>? _changes;
  Timer? _debounce, _fallback;
  String? _celebration;
  Timer? _celebrationExpiry;
  int _epoch = 0, _seen = 0;
  bool _hidden = false, _loading = false, _dirty = false, _baseline = true;
  String? _error;
  bool get _active => mounted && !_hidden && widget.repository.isActive;
  String l(String text) => academicText(context, text);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _listen();
    unawaited(_load());
  }

  void _listen() {
    _changes = widget.repository.changes.listen((event) {
      if (!_active ||
          event.schoolId != widget.repository.schoolId ||
          (event.learnerId != null && event.learnerId != widget.learnerId))
        return;
      _debounce?.cancel();
      _debounce = Timer(
        const Duration(milliseconds: 150),
        () => unawaited(_load()),
      );
    });
    _fallback = Timer.periodic(const Duration(seconds: 15), (_) {
      if (_active) unawaited(_load());
    });
  }

  void _erase() {
    _epoch++;
    _counters = null;
    _seen = 0;
    _baseline = true;
    _loading = false;
    _dirty = false;
    _error = null;
    _debounce?.cancel();
    _celebrationExpiry?.cancel();
    _celebration = null;
  }

  @override
  void didUpdateWidget(RecognitionHomeSummary oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.repository != widget.repository ||
        oldWidget.learnerId != widget.learnerId) {
      unawaited(_changes?.cancel());
      _fallback?.cancel();
      _erase();
      _listen();
      unawaited(_load());
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      setState(() {
        _hidden = true;
        _erase();
      });
    } else {
      setState(() => _hidden = false);
      unawaited(_load());
    }
  }

  Future<void> _load() async {
    if (!_active) return;
    if (_loading) {
      _dirty = true;
      return;
    }
    final epoch = _epoch;
    bool current() => _active && epoch == _epoch;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final access = await widget.repository.access('house_points');
      if (!current()) return;
      final counters = await widget.repository.counters(access.sessionYearId);
      if (!current()) return;
      final history = await widget.repository.points(
        widget.learnerId,
        access.sessionYearId,
        family: true,
      );
      if (!current()) return;
      final fresh = history.items
          .where(
            (point) =>
                point.id > _seen &&
                point.points > 0 &&
                point.reversesId == null,
          )
          .toList();
      final celebrate = !_baseline && fresh.isNotEmpty ? fresh.first : null;
      setState(() {
        _counters = counters;
        _seen = history.items.fold(
          _seen,
          (value, point) => point.id > value ? point.id : value,
        );
        _baseline = false;
      });
      if (celebrate != null && mounted) {
        _celebrationExpiry?.cancel();
        setState(
          () => _celebration =
              '${celebrate.points} ${l('house points')} · ${celebrate.houseName}',
        );
        _celebrationExpiry = Timer(const Duration(seconds: 4), () {
          if (mounted) setState(() => _celebration = null);
        });
      }
    } catch (_) {
      if (!current()) return;
      // A failed protected refresh never leaves a stale counter visible.
      setState(() {
        _counters = null;
        _error = 'House points are unavailable. Refresh to check your access.';
        _celebrationExpiry?.cancel();
        _celebration = null;
      });
    } finally {
      if (mounted && epoch == _epoch) {
        final again = _dirty;
        setState(() {
          _loading = false;
          _dirty = false;
        });
        if (again && _active) unawaited(_load());
      }
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _erase();
    _fallback?.cancel();
    unawaited(_changes?.cancel());
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!_active) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_celebration != null)
            Semantics(
              liveRegion: true,
              child: Container(
                key: const ValueKey('home-house-moment'),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.primaryContainer,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.celebration_outlined),
                    const SizedBox(width: 12),
                    Expanded(child: Text(_celebration!)),
                  ],
                ),
              ),
            ),
          if (_loading && _counters == null) const LinearProgressIndicator(),
          if (_error != null)
            Row(
              children: [
                Expanded(child: Text(l(_error!))),
                IconButton(
                  tooltip: l('Refresh'),
                  onPressed: _loading ? null : _load,
                  icon: const Icon(Icons.refresh),
                ),
              ],
            ),
          for (final house in _counters?.items ?? <RecognitionHouseCounter>[])
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      house.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    '${house.points} ${l('house points')}',
                    key: ValueKey('home-house-${house.id}'),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
