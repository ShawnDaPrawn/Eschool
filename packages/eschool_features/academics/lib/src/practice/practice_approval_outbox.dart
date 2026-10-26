import 'dart:convert';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import '../content/content_store.dart';
import 'practice_repository.dart';

class PracticeApprovalRecovery {
  const PracticeApprovalRecovery({this.intent, this.blocked = false});
  final PracticeIntent? intent;
  final bool blocked;
}

/// Only exact submitted operations are persisted, never an unsaved editor draft.
/// The original account/school/group/source is reauthorized before recovery.
class PracticeApprovalOutbox {
  PracticeApprovalOutbox(
    this.repository, {
    required this.store,
    this.environment = AppConfig.apiV2Url,
    DateTime Function()? clock,
  }) : clock = clock ?? DateTime.now;
  final PracticeRepository repository;
  final Future<ContentStore> Function() store;
  final String environment;
  final DateTime Function() clock;
  Future<ContentStore>? _opened;
  Future<void> _tail = Future.value();
  bool _open = true;
  bool get isActive => _open && repository.isActive;
  void close() {
    _open = false;
  }

  void _guard() {
    if (!isActive) throw StateError('The approval context changed.');
  }

  String _key(int group) =>
      '$environment:${repository.schoolId}:${repository.readerId}:approval:$group';
  Future<T> _locked<T>(Future<T> Function(ContentStore) run) {
    final result = _tail.then((_) async {
      _guard();
      final box = await (_opened ??= store());
      _guard();
      final value = await run(box);
      _guard();
      return value;
    });
    _tail = result.then<void>((_) {}, onError: (Object _, StackTrace _) {});
    return result;
  }

  Future<PracticeBankChoices> _authorize(int group) async {
    _guard();
    final access = await repository.access();
    _guard();
    if (!access.staffGroups.any((g) => g.id == group)) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey, statusCode: 403);
    }
    final choices = await repository.choices(group);
    _guard();
    return choices;
  }

  bool _scope(Map<String, dynamic> raw, int group) =>
      raw['environment'] == environment &&
      raw['school'] == repository.schoolId &&
      raw['reader'] == repository.readerId &&
      raw['group'] == group;
  bool _source(Map<String, dynamic> raw, PracticeBankChoices choices) =>
      choices.sources.any((s) => s.id == raw['source']) &&
      choices.topics.any((t) => t.id == raw['topic']);
  Future<Map<String, dynamic>?> _read(ContentStore box, String key) async {
    final raw = await box.read(key);
    _guard();
    if (raw == null) return null;
    final now = clock().toUtc();
    final saved = DateTime.tryParse(
      raw['saved_at'] is String ? raw['saved_at'] as String : '',
    );
    if (saved == null || now.isBefore(saved)) {
      // An unknown approval is never silently forgotten: doing so could issue a new UUID.
      throw const FormatException(
        'The saved approval clock cannot be verified.',
      );
    }
    return raw;
  }

  Future<PracticeApprovalRecovery> restore(int group) async {
    final choices = await _authorize(group);
    return _locked((box) async {
      final raw = await _read(box, _key(group));
      if (raw == null) return const PracticeApprovalRecovery();
      if (!_scope(raw, group)) {
        throw const FormatException('Invalid saved approval scope.');
      }
      // Decode/display no submitted question until its original source remains permitted.
      if (!_source(raw, choices)) {
        return const PracticeApprovalRecovery(blocked: true);
      }
      final encoded = raw['intent'];
      if (encoded is! String || utf8.encode(encoded).length > 1024 * 1024) {
        throw const FormatException('Invalid saved approval.');
      }
      final intent = PracticeIntent.restoreApproval(
        Map<String, dynamic>.from(jsonDecode(encoded) as Map),
      );
      if (intent.group != group ||
          intent.body['content_version_id'] != raw['source'] ||
          intent.body['topic_id'] != raw['topic']) {
        throw const FormatException('Invalid saved approval source.');
      }
      return PracticeApprovalRecovery(intent: intent);
    });
  }

  Future<void> stage(PracticeIntent intent) async {
    if (intent.action != PracticeAction.approve || intent.group == null) {
      throw const FormatException('Invalid approval operation.');
    }
    final encoded = jsonEncode(intent.savedApproval());
    if (utf8.encode(encoded).length > 1024 * 1024) {
      throw const FormatException('The saved approval is too large.');
    }
    PracticeIntent.restoreApproval(intent.savedApproval());
    final choices = await _authorize(intent.group!);
    if (!choices.writable) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey, statusCode: 403);
    }
    await _locked((box) async {
      final value = {
        'environment': environment,
        'school': repository.schoolId,
        'reader': repository.readerId,
        'group': intent.group,
        'source': intent.body['content_version_id'],
        'topic': intent.body['topic_id'],
        'saved_at': clock().toUtc().toIso8601String(),
        'intent': jsonEncode(intent.savedApproval()),
      };
      if (!_source(value, choices)) {
        throw ApiException(
          ApiErrorCodes.defaultErrorMessageKey,
          statusCode: 403,
        );
      }
      final old = await _read(box, _key(intent.group!));
      if (old != null &&
          (!_scope(old, intent.group!) || old['intent'] != value['intent'])) {
        throw StateError(
          'Resolve the original saved approval before changing it.',
        );
      }
      for (final key in await box.keys()) {
        await _read(box, key);
      }
      await box.write(_key(intent.group!), old ?? value);
    });
  }

  Future<void> complete(PracticeIntent intent) => _locked((box) async {
    final key = _key(intent.group!), raw = await _read(box, key);
    if (raw == null) return;
    if (!_scope(raw, intent.group!) ||
        raw['intent'] != jsonEncode(intent.savedApproval())) {
      throw StateError('The saved approval changed.');
    }
    await box.remove(key);
  });
}
