import 'dart:convert';

import 'package:crypto/crypto.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

import '../content/content_store.dart';
import 'practice_answers.dart';
import 'practice_repository.dart';

class PendingPractice {
  const PendingPractice(this.learner, this.set, this.count);
  final int learner, set, count;
}

class SavedPractice {
  const SavedPractice(this.set, this.pending, this.expiresAt, this.writable);
  final PracticeDailySet set;
  final List<PracticeIntent> pending;
  final DateTime expiresAt;
  final bool writable;
}

/// Own explicit copies, serialized exact reviews and a server-bounded offline lease.
/// Expired question bodies are removed; encrypted pending work can synchronize only
/// after fresh server access/source authorization, for at most seven calendar days.
class PracticeCache {
  PracticeCache(
    this.repository, {
    required this.store,
    required this.canQueue,
    this.onRevoke,
    this.environment = AppConfig.apiV2Url,
    DateTime Function()? clock,
  }) : clock = clock ?? DateTime.now;
  final PracticeRepository repository;
  final Future<ContentStore> Function() store;
  final String environment;
  final bool Function() canQueue;
  final Future<void> Function()? onRevoke;
  final DateTime Function() clock;
  bool _open = true;
  Future<ContentStore>? _opened;
  Future<void> _tail = Future.value();
  bool get isActive => _open && repository.isActive;
  void close() {
    _open = false;
  }

  void _guard() {
    if (!isActive) throw StateError('The practice context changed.');
  }

  String _key(int learner, int set) => sha256
      .convert(
        utf8.encode(
          '$environment:${repository.schoolId}:${repository.readerId}:$learner:$set',
        ),
      )
      .toString();
  Future<T> _locked<T>(Future<T> Function(ContentStore) run) {
    final operation = _tail.then((_) async {
      _guard();
      final box = await (_opened ??= store());
      _guard();
      final result = await run(box);
      _guard();
      return result;
    });
    _tail = operation.then<void>((_) {}, onError: (Object _, StackTrace _) {});
    return operation;
  }

  bool _valid(PracticeDailySet value) =>
      value.schoolId == repository.schoolId &&
      value.readerId == repository.readerId &&
      value.learnerId > 0 &&
      value.id > 0 &&
      value.groupId > 0 &&
      RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(value.practiceOn) &&
      value.items.isNotEmpty &&
      value.items.length <= 10 &&
      value.items.map((e) => e.id).toSet().length == value.items.length &&
      value.estimatedSeconds > 0 &&
      value.estimatedSeconds <= 600 &&
      value.items.fold<int>(0, (sum, e) => sum + e.estimatedSeconds) ==
          value.estimatedSeconds &&
      utf8.encode(jsonEncode(value.toJson())).length <= 1024 * 1024 &&
      value.items.every(
        (e) =>
            e.id > 0 &&
            e.estimatedSeconds >= 60 &&
            e.estimatedSeconds <= 300 &&
            e.definition.prompt.runes.length <= 4000 &&
            e.definition.explanation.runes.length <= 4000 &&
            e.definition.options.length <= 6 &&
            e.definition.answers.isNotEmpty &&
            e.definition.answers.length <= 10 &&
            [
              PracticeQuestionKindEnum.choice,
              PracticeQuestionKindEnum.number,
            ].contains(e.definition.kind),
      );
  Map<String, dynamic> _scope(PracticeDailySet value) => {
    'school': repository.schoolId,
    'reader': repository.readerId,
    'learner': value.learnerId,
    'id': value.id,
    'group': value.groupId,
    'entry_ids': value.items.map((e) => e.id).toList(),
  };
  bool _matching(Map<String, dynamic> raw, int learner, int set) =>
      raw['school'] == repository.schoolId &&
      raw['reader'] == repository.readerId &&
      raw['learner'] == learner &&
      raw['id'] == set;
  Future<Map<String, dynamic>?> _raw(
    ContentStore box,
    int learner,
    int set,
  ) async {
    final key = _key(learner, set), raw = await box.read(key);
    _guard();
    if (raw == null) return null;
    try {
      if (!_matching(raw, learner, set)) {
        throw const FormatException('Wrong scope.');
      }
      final time = clock().toUtc(),
          requested = DateTime.parse(raw['requested_at'] as String).toUtc(),
          last = DateTime.parse(raw['last_seen'] as String).toUtc(),
          expiry = DateTime.parse(raw['expires_at'] as String).toUtc();
      if (time.isBefore(requested) ||
          time.isBefore(last) ||
          expiry.isBefore(requested) ||
          expiry.difference(requested) > const Duration(days: 1) ||
          time.difference(requested) > const Duration(days: 7)) {
        throw const FormatException('Invalid cache time.');
      }
      final entries = List<int>.from(raw['entry_ids'] as List);
      final pending = (raw['pending'] as List)
          .map(
            (e) => PracticeIntent.restoreReview(
              Map<String, dynamic>.from(e as Map),
            ),
          )
          .toList();
      if (entries.length > 10 ||
          pending.length > 10 ||
          pending.map((p) => p.entry).toSet().length != pending.length ||
          pending.any(
            (p) =>
                p.learner != learner ||
                p.set != set ||
                !entries.contains(p.entry),
          )) {
        throw const FormatException('Invalid pending scope.');
      }
      raw['last_seen'] = time.toIso8601String();
      if (!time.isBefore(expiry)) raw['set'] = null;
    } on FormatException {
      await box.remove(key);
      rethrow;
    } on TypeError {
      await box.remove(key);
      throw const FormatException('Invalid practice copy.');
    }
    // Storage or context failures preserve an existing exact pending operation.
    await box.write(key, raw);
    _guard();
    return raw;
  }

  Future<void> save(
    PracticeDailySet value, {
    required DateTime requestedAt,
    bool writable = false,
  }) => _locked((box) async {
    if (!_valid(value)) {
      throw const FormatException('Invalid own practice set.');
    }
    final duration = value.offlineUntil.difference(value.serverTime),
        now = clock().toUtc(),
        start = requestedAt.toUtc();
    if (duration <= Duration.zero ||
        duration > const Duration(days: 1) ||
        now.isBefore(start)) {
      throw const FormatException(
        'This practice copy has no current offline lease.',
      );
    }
    final relative = start.add(duration),
        expiry = relative.isBefore(value.offlineUntil)
            ? relative
            : value.offlineUntil;
    if (!now.isBefore(expiry)) {
      throw const FormatException('The practice copy has expired.');
    }
    final old = await _raw(box, value.learnerId, value.id);
    await box.write(
      _key(value.learnerId, value.id),
      _scope(value)..addAll({
        'requested_at': start.toIso8601String(),
        'expires_at': expiry.toUtc().toIso8601String(),
        'last_seen': now.toIso8601String(),
        'set': value.toJson(),
        'pending': old?['pending'] ?? <dynamic>[],
        'writable': writable,
      }),
    );
  });
  Future<SavedPractice?> read(int learner, int set) => _locked((box) async {
    final raw = await _raw(box, learner, set);
    if (raw == null || raw['set'] == null) return null;
    final value = PracticeDailySet.fromJson(
      Map<String, dynamic>.from(raw['set'] as Map),
    );
    if (!_valid(value) || value.id != set || value.learnerId != learner) {
      await box.remove(_key(learner, set));
      throw const FormatException('Invalid practice copy.');
    }
    return SavedPractice(
      value,
      (raw['pending'] as List)
          .map(
            (e) => PracticeIntent.restoreReview(
              Map<String, dynamic>.from(e as Map),
            ),
          )
          .toList(),
      DateTime.parse(raw['expires_at'] as String),
      raw['writable'] == true,
    );
  });
  Future<void> queue(PracticeIntent intent) => _locked((box) async {
    if (!canQueue()) {
      throw StateError(
        'Practice is read only. Refresh access before saving answers.',
      );
    }
    if (intent.action != PracticeAction.review) {
      throw const FormatException('Only review operations are saved offline.');
    }
    final raw = await _raw(box, intent.learner!, intent.set!);
    if (raw == null || raw['set'] == null) {
      throw const FormatException(
        'Download a current own practice set before saving offline answers.',
      );
    }
    if (raw['writable'] != true) {
      throw StateError(
        'Sync current writable access before saving offline answers.',
      );
    }
    final value = PracticeDailySet.fromJson(
      Map<String, dynamic>.from(raw['set'] as Map),
    );
    final entries = value.items.where((e) => e.id == intent.entry).toList();
    if (entries.length != 1 || entries.single.review != null) {
      throw const FormatException('This question already has a saved review.');
    }
    LocalPracticeAnswers.correct(
      entries.single.definition,
      intent.body['answer'] as String,
    );
    final pending = List<dynamic>.from(raw['pending'] as List);
    final old = pending
        .map(
          (e) =>
              PracticeIntent.restoreReview(Map<String, dynamic>.from(e as Map)),
        )
        .where((e) => e.entry == intent.entry)
        .firstOrNull;
    if (old != null) {
      if (jsonEncode(old.savedReview()) != jsonEncode(intent.savedReview())) {
        throw StateError('Retry the saved answer before changing it.');
      }
      return;
    }
    pending.add(intent.savedReview());
    raw['pending'] = pending;
    await box.write(_key(intent.learner!, intent.set!), raw);
  });
  Future<PracticeDailySet> synchronize(int learner, int set) => _locked((
    box,
  ) async {
    // Neither expired pending bodies nor source details are returned before these fresh checks.
    late PracticeAccess access;
    late PracticeDailySet current;
    try {
      access = await repository.access();
      _guard();
      if (access.learnerId != learner) {
        throw ApiException(
          ApiErrorCodes.defaultErrorMessageKey,
          statusCode: 403,
        );
      }
      current = await repository.daily(learner, set);
    } on ApiException catch (error) {
      if ([401, 403, 404].contains(error.statusCode)) {
        await box.remove(_key(learner, set));
      }
      rethrow;
    }
    _guard();
    final raw = await _raw(box, learner, set);
    if (raw == null) return current;
    raw['writable'] = access.writable;
    await box.write(_key(learner, set), raw);
    if (!access.writable) return current;
    final pending = List<dynamic>.from(raw['pending'] as List);
    while (pending.isNotEmpty) {
      final intent = PracticeIntent.restoreReview(
        Map<String, dynamic>.from(pending.first as Map),
      );
      if (!current.items.any((e) => e.id == intent.entry)) {
        throw ApiException(
          ApiErrorCodes.defaultErrorMessageKey,
          statusCode: 403,
        );
      }
      await repository.review(intent);
      _guard();
      pending.removeAt(0);
      raw['pending'] = List<dynamic>.from(pending);
      await box.write(_key(learner, set), raw);
      _guard();
    }
    current = await repository.daily(learner, set);
    _guard();
    // A failed final refresh never changes the exact operations already confirmed above.
    if (_valid(current) && clock().toUtc().isBefore(current.offlineUntil)) {
      final start = clock().toUtc(),
          duration = current.offlineUntil.difference(current.serverTime);
      if (duration > Duration.zero && duration <= const Duration(days: 1)) {
        final relative = start.add(duration);
        raw['requested_at'] = start.toIso8601String();
        raw['expires_at'] =
            (relative.isBefore(current.offlineUntil)
                    ? relative
                    : current.offlineUntil)
                .toUtc()
                .toIso8601String();
        raw['set'] = current.toJson();
        await box.write(_key(learner, set), raw);
      }
    }
    return current;
  });
  Future<List<PendingPractice>> pending() => _locked((box) async {
    final result = <PendingPractice>[];
    for (final key in await box.keys()) {
      final scope = await box.read(key);
      if (scope == null || scope['learner'] is! int || scope['id'] is! int) {
        continue;
      }
      Map<String, dynamic>? raw;
      try {
        raw = await _raw(box, scope['learner'] as int, scope['id'] as int);
      } on FormatException {
        continue;
      }
      if (raw != null && (raw['pending'] as List).isNotEmpty) {
        result.add(
          PendingPractice(
            raw['learner'] as int,
            raw['id'] as int,
            (raw['pending'] as List).length,
          ),
        );
      }
    }
    return result;
  });
  Future<List<SavedPractice>> saved() => _locked((box) async {
    final result = <SavedPractice>[];
    for (final key in await box.keys()) {
      final scope = await box.read(key);
      if (scope == null || scope['learner'] is! int || scope['id'] is! int) {
        continue;
      }
      Map<String, dynamic>? raw;
      try {
        raw = await _raw(box, scope['learner'] as int, scope['id'] as int);
      } on FormatException {
        continue;
      }
      if (raw == null || raw['set'] == null) continue;
      final set = PracticeDailySet.fromJson(
        Map<String, dynamic>.from(raw['set'] as Map),
      );
      if (!_valid(set)) {
        await box.remove(key);
        continue;
      }
      result.add(
        SavedPractice(
          set,
          (raw['pending'] as List)
              .map(
                (p) => PracticeIntent.restoreReview(
                  Map<String, dynamic>.from(p as Map),
                ),
              )
              .toList(),
          DateTime.parse(raw['expires_at'] as String),
          raw['writable'] == true,
        ),
      );
    }
    return result;
  });
  Future<void> discardReview(int learner, int set, int entry) =>
      _locked((box) async {
        final raw = await _raw(box, learner, set);
        if (raw == null) return;
        raw['pending'] = (raw['pending'] as List)
            .where(
              (value) =>
                  PracticeIntent.restoreReview(
                    Map<String, dynamic>.from(value as Map),
                  ).entry !=
                  entry,
            )
            .toList();
        await box.write(_key(learner, set), raw);
      });
  Future<void> retainGroups(Set<int> groups) => _locked((box) async {
    for (final key in await box.keys()) {
      final raw = await box.read(key);
      if (raw == null || !groups.contains(raw['group'])) {
        await box.remove(key);
      }
    }
  });
  Future<void> revoke() async {
    try {
      await clear();
    } finally {
      _open = false;
      // Production erasure is scope-specific and remains effective after route disposal.
      await onRevoke?.call();
    }
  }

  Future<void> clear() => _locked((box) async {
    for (final key in await box.keys()) {
      await box.remove(key);
    }
  });
  Future<void> remove(int learner, int set) =>
      _locked((box) => box.remove(_key(learner, set)));
}
