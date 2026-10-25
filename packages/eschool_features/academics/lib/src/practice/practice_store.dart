import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:hive/hive.dart';
import 'package:eschool_core/eschool_core.dart' show AppConfig;

import '../content/content_store.dart' show ContentStore;

/// Dedicated encrypted store; changing school/account destroys the old key.
/// Every queued operation checks its scope and generation under the same lock.
class SecurePracticeStore implements ContentStore {
  SecurePracticeStore._(this.scope, this.generation, this.active);
  final String scope;
  final int generation;
  final bool Function() active;
  static const _name = 'academic_practice_cache_v1';
  static const _keyName = 'academic_practice_cache_key_v1';
  static const _scopeName = 'academic_practice_cache_scope_v1';
  static const _secure = FlutterSecureStorage();
  static Future<void> _tail = Future<void>.value();
  static Box<dynamic>? _box;
  static String? _scope;
  static int _generation = 0;
  static Future<T> _locked<T>(Future<T> Function() action) {
    final operation = _tail.then((_) => action());
    _tail = operation.then<void>(
      (_) {},
      onError: (Object error, StackTrace stack) {},
    );
    return operation;
  }

  static Future<void> _erase() async {
    final box = _box;
    _box = null;
    _scope = null;
    _generation++;
    try {
      try {
        if (box?.isOpen == true) await box!.close();
      } finally {
        await Hive.deleteBoxFromDisk(_name);
      }
    } finally {
      try {
        await _secure.delete(key: _keyName);
      } finally {
        await _secure.delete(key: _scopeName);
      }
    }
  }

  static Future<SecurePracticeStore> open(
    int account,
    int school, {
    required bool Function() active,
    String environment = AppConfig.apiV2Url,
  }) => _locked(() async {
    if (account <= 0 || school <= 0 || !active()) {
      throw StateError('The practice context changed.');
    }
    final scope = '$environment:practice:$account:$school';
    if (_scope == scope && _box?.isOpen == true) {
      return SecurePracticeStore._(scope, _generation, active);
    }
    final recorded = await _secure.read(key: _scopeName);
    if (!active()) throw StateError('The practice context changed.');
    if (recorded != scope) await _erase();
    var encoded = await _secure.read(key: _keyName);
    if (!active()) throw StateError('The practice context changed.');
    if (encoded == null) {
      encoded = base64UrlEncode(Hive.generateSecureKey());
      await _secure.write(key: _keyName, value: encoded);
      await _secure.write(key: _scopeName, value: scope);
    }
    _box = await Hive.openBox<dynamic>(
      _name,
      encryptionCipher: HiveAesCipher(base64Url.decode(encoded)),
    );
    _scope = scope;
    if (!active()) {
      await _erase();
      throw StateError('The practice context changed.');
    }
    return SecurePracticeStore._(scope, _generation, active);
  });

  /// A late revoked route cannot erase a replacement account or school key.
  static Future<void> revokeScope(
    int account,
    int school, {
    String environment = AppConfig.apiV2Url,
  }) => _locked(() async {
    final expected = '$environment:practice:$account:$school';
    if (_scope == expected || await _secure.read(key: _scopeName) == expected) {
      await _erase();
    }
  });
  static Future<void> purge() => _locked(_erase);
  Box<dynamic> _checked() {
    if (!active() ||
        _scope != scope ||
        _generation != generation ||
        _box?.isOpen != true) {
      throw StateError('The practice context changed.');
    }
    return _box!;
  }

  @override
  Future<Map<String, dynamic>?> read(String key) => _locked(() async {
    final value = _checked().get(key);
    return value == null
        ? null
        : Map<String, dynamic>.from(jsonDecode(jsonEncode(value)) as Map);
  });
  @override
  Future<void> write(String key, Map<String, dynamic> value) =>
      _locked(() async {
        final box = _checked();
        if (!box.containsKey(key) && box.length >= 20) {
          throw StateError(
            'Sync or remove a saved practice set before downloading another.',
          );
        }
        _checked();
        await box.put(key, value);
      });
  @override
  Future<void> remove(String key) => _locked(() async {
    await _checked().delete(key);
  });
  @override
  Future<List<String>> keys() =>
      _locked(() async => _checked().keys.whereType<String>().toList());
}
