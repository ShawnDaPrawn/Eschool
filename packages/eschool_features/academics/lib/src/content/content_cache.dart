import 'dart:convert';
import 'package:crypto/crypto.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart' show AppConfig;
import 'content_store.dart';
import 'content_repository.dart';

class SavedContent {
  const SavedContent(this.reader, this.requestedAt, this.expiresAt);
  final ContentReader reader;
  final DateTime requestedAt, expiresAt;
}

/// Explicit copies only. Serializes eviction, renewal, expiry and private writes.
class ContentReaderCache {
  ContentReaderCache(
    this.repository, {
    required this.store,
    this.environment = AppConfig.apiV2Url,
    DateTime Function()? clock,
  }) : clock = clock ?? DateTime.now;
  final AcademicContentRepository repository;
  final String environment;
  final Future<ContentStore> Function() store;
  final DateTime Function() clock;
  bool _open = true;
  Future<ContentStore>? _opened;
  Future<void> _tail = Future<void>.value();
  static const maximum = 20;
  bool get isActive => _open && repository.isActive;
  void close() {
    _open = false;
  }

  void _guard() {
    if (!isActive) throw StateError('The content context changed.');
  }

  Future<T> _locked<T>(Future<T> Function(ContentStore) action) {
    final operation = _tail.then((_) async {
      try {
        _guard();
        final box = await (_opened ??= store());
        _guard();
        return await action(box);
      } catch (_) {
        _opened = null;
        rethrow;
      }
    });
    _tail = operation.then<void>(
      (_) {},
      onError: (Object error, StackTrace stack) {},
    );
    return operation;
  }

  String _key(int learner, int version) => sha256
      .convert(
        utf8.encode(
          '$environment:${repository.readerId}:${repository.schoolId}:$learner:$version',
        ),
      )
      .toString();
  bool _valid(ContentReader reader) =>
      reader.readerId == repository.readerId &&
      reader.schoolId == repository.schoolId &&
      reader.learnerId > 0 &&
      reader.id > 0 &&
      utf8.encode(reader.markdown).length <= 2 * 1024 * 1024 &&
      reader.markdown.isNotEmpty &&
      sha256.convert(utf8.encode(reader.markdown)).toString() ==
          reader.bodySha256 &&
      reader.offlineUntil.difference(reader.serverTime) > Duration.zero &&
      reader.offlineUntil.difference(reader.serverTime) <=
          const Duration(days: 1);
  DateTime _expiry(ContentReader reader, DateTime requestedAt) {
    final relative = requestedAt.add(
      reader.offlineUntil.difference(reader.serverTime),
    );
    return relative.isBefore(reader.offlineUntil)
        ? relative
        : reader.offlineUntil;
  }

  Future<SavedContent?> _read(ContentStore box, String key) async {
    final raw = await box.read(key);
    if (raw == null) return null;
    try {
      final reader = ContentReader.fromJson(
        Map<String, dynamic>.from(raw['reader'] as Map),
      );
      final start = DateTime.parse(raw['requested_at'] as String).toUtc(),
          expiry = DateTime.parse(raw['expires_at'] as String).toUtc(),
          lastSeen = DateTime.parse(raw['last_seen'] as String).toUtc();
      final now = clock().toUtc();
      if (!_valid(reader) ||
          key != _key(reader.learnerId, reader.id) ||
          expiry != _expiry(reader, start) ||
          now.isBefore(start) ||
          now.isBefore(lastSeen) ||
          !now.isBefore(expiry)) {
        throw const FormatException('Expired private copy');
      }
      _guard();
      await box.write(key, {...raw, 'last_seen': now.toIso8601String()});
      return SavedContent(reader, start, expiry);
    } catch (_) {
      await box.remove(key);
      return null;
    }
  }

  Future<SavedContent?> read(int learner, int version) =>
      _locked((box) => _read(box, _key(learner, version)));
  Future<List<SavedContent>> list(int learner) => _locked((box) async {
    final values = <SavedContent>[];
    for (final key in await box.keys()) {
      final row = await _read(box, key);
      if (row?.reader.learnerId == learner) values.add(row!);
    }
    _guard();
    values.sort((a, b) => b.requestedAt.compareTo(a.requestedAt));
    return values;
  });
  Map<String, dynamic> _encode(ContentReader reader, DateTime requestedAt) {
    final expiry = _expiry(reader, requestedAt), now = clock().toUtc();
    if (!_valid(reader) || now.isBefore(requestedAt) || !now.isBefore(expiry)) {
      throw StateError('Refresh this material before saving it offline.');
    }
    return {
      'reader': reader.toJson(),
      'requested_at': requestedAt.toUtc().toIso8601String(),
      'expires_at': expiry.toIso8601String(),
      'last_seen': now.toIso8601String(),
    };
  }

  Future<void> _put(
    ContentStore box,
    ContentReader reader,
    DateTime requestedAt,
  ) async {
    final value = _encode(reader, requestedAt);
    final key = _key(reader.learnerId, reader.id),
        rows = <MapEntry<String, DateTime>>[];
    for (final existing in await box.keys()) {
      final row = await _read(box, existing);
      if (row != null && existing != key) {
        rows.add(MapEntry(existing, row.requestedAt));
      }
    }
    rows.sort((a, b) => a.value.compareTo(b.value));
    while (rows.length >= maximum) {
      await box.remove(rows.removeAt(0).key);
    }
    _guard();
    await box.write(key, value);
  }

  Future<void> save(ContentReader reader, {required DateTime requestedAt}) =>
      _locked((box) => _put(box, reader, requestedAt.toUtc()));
  Future<void> remove(int learner, int version) =>
      _locked((box) => box.remove(_key(learner, version)));
  Future<void> removeLearner(int learner) => _locked((box) async {
    for (final key in await box.keys()) {
      final raw = await box.read(key);
      if (raw?['reader'] is! Map || raw!['reader']['learner_id'] == learner) {
        await box.remove(key);
      }
    }
  });
  Future<void> revalidate(int learner) async {
    final copies = await list(learner);
    if (copies.isEmpty) return;
    final start = clock().toUtc(),
        versions = copies.map((row) => row.reader.id).toSet();
    final access = await repository.savedAccess(learner, versions);
    _guard();
    if (access.schoolId != repository.schoolId ||
        access.readerId != repository.readerId ||
        access.learnerId != learner ||
        !versions.containsAll(access.allowedVersionIds) ||
        access.offlineUntil.difference(access.serverTime) <= Duration.zero ||
        access.offlineUntil.difference(access.serverTime) >
            const Duration(days: 1)) {
      throw StateError('Saved material needs a fresh access check.');
    }
    await _locked((box) async {
      for (final row in copies) {
        final current = await _read(box, _key(learner, row.reader.id));
        if (current == null || current.requestedAt.isAfter(start)) continue;
        if (!access.allowedVersionIds.contains(row.reader.id)) {
          await box.remove(_key(learner, row.reader.id));
          continue;
        }
        final renewed = ContentReader.fromJson({
          ...row.reader.toJson(),
          'server_time': access.serverTime.toIso8601String(),
          'offline_until': access.offlineUntil.toIso8601String(),
        });
        _guard();
        await box.write(_key(learner, row.reader.id), _encode(renewed, start));
      }
    });
  }
}
