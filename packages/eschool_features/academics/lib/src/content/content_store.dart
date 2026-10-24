import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:hive/hive.dart';
import 'package:eschool_core/eschool_core.dart' show AppConfig;

abstract interface class ContentStore {
  Future<Map<String, dynamic>?> read(String key);
  Future<void> write(String key, Map<String, dynamic> value);
  Future<void> remove(String key);
  Future<List<String>> keys();
}

class MemoryContentStore implements ContentStore {
  final _values = <String, Map<String, dynamic>>{};
  @override
  Future<Map<String, dynamic>?> read(String key) async => _values[key] == null
      ? null
      : Map<String, dynamic>.from(jsonDecode(jsonEncode(_values[key])) as Map);
  @override
  Future<void> write(String key, Map<String, dynamic> value) async {
    if (!_values.containsKey(key) && _values.length >= 20) {
      _values.remove(_values.keys.first);
    }
    _values[key] = Map<String, dynamic>.from(
      jsonDecode(jsonEncode(value)) as Map,
    );
  }

  @override
  Future<void> remove(String key) async {
    _values.remove(key);
  }

  @override
  Future<List<String>> keys() async => _values.keys.toList();
}

/// Dedicated encrypted store; changing school/account destroys the old key.
/// Every queued operation checks its scope and generation under the same lock.
class SecureContentStore implements ContentStore {
  SecureContentStore._(this.scope, this.generation, this.active);
  final String scope;
  final int generation;
  final bool Function() active;
  static const _name = 'academic_content_cache_v1';
  static const _keyName = 'academic_content_cache_key_v1';
  static const _scopeName = 'academic_content_cache_scope_v1';
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

  static Future<SecureContentStore> open(
    int account,
    int school, {
    required bool Function() active,
    String environment = AppConfig.apiV2Url,
  }) => _locked(() async {
    if (account <= 0 || school <= 0 || !active()) {
      throw StateError('The content context changed.');
    }
    final scope = '$environment:$account:$school';
    if (_scope == scope && _box?.isOpen == true) {
      return SecureContentStore._(scope, _generation, active);
    }
    final recorded = await _secure.read(key: _scopeName);
    if (!active()) throw StateError('The content context changed.');
    if (recorded != scope) await _erase();
    var encoded = await _secure.read(key: _keyName);
    if (!active()) throw StateError('The content context changed.');
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
      throw StateError('The content context changed.');
    }
    return SecureContentStore._(scope, _generation, active);
  });
  static Future<void> purge() => _locked(_erase);
  Box<dynamic> _checked() {
    if (!active() ||
        _scope != scope ||
        _generation != generation ||
        _box?.isOpen != true) {
      throw StateError('The content context changed.');
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
          await box.delete(box.keys.first);
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
