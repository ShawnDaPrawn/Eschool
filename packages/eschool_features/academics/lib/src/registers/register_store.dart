import 'dart:async';
import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:hive/hive.dart';

abstract interface class RegisterStore {
  Future<Map<String, dynamic>?> read(String key);
  Future<void> write(String key, Map<String, dynamic> value);
  Future<void> remove(String key);
  Future<List<String>> keys();
}

class MemoryRegisterStore implements RegisterStore {
  final Map<String, Map<String, dynamic>> _values = {};
  @override
  Future<Map<String, dynamic>?> read(String key) async => _values[key] == null
      ? null
      : Map<String, dynamic>.from(jsonDecode(jsonEncode(_values[key])) as Map);
  @override
  Future<void> write(String key, Map<String, dynamic> value) async =>
      _values[key] = Map<String, dynamic>.from(
        jsonDecode(jsonEncode(value)) as Map,
      );
  @override
  Future<void> remove(String key) async {
    _values.remove(key);
  }

  @override
  Future<List<String>> keys() async => _values.keys.toList();
}

/// Encrypted Hive data; its random key is held in the platform's secure storage.
/// All reads/writes/open/purge serialize, so a late prior-account write cannot
/// repopulate a new account's box after sign-out or account switching.
class SecureRegisterStore implements RegisterStore {
  SecureRegisterStore._(this.owner);
  final int owner;
  static const _name = 'academic_register_cache_v1';
  static const _keyName = 'academic_register_cache_key_v1';
  static const _ownerName = 'academic_register_cache_owner_v1';
  static const _secure = FlutterSecureStorage();
  static Future<void> _tail = Future<void>.value();
  static Box<dynamic>? _box;
  static int? _owner;
  static Future<T> _locked<T>(Future<T> Function() action) {
    final complete = Completer<T>();
    _tail = _tail.then((_) async {
      try {
        complete.complete(await action());
      } catch (error, stack) {
        complete.completeError(error, stack);
      }
    });
    return complete.future;
  }

  static Future<void> _erase() async {
    final box = _box;
    _box = null;
    _owner = null;
    try {
      try {
        if (box?.isOpen == true) await box!.close();
      } finally {
        await Hive.deleteBoxFromDisk(_name);
      }
    } finally {
      // Attempt cryptographic erasure even if a device cannot delete the file.
      try {
        await _secure.delete(key: _keyName);
      } finally {
        await _secure.delete(key: _ownerName);
      }
    }
  }

  static Future<SecureRegisterStore> open(int owner) => _locked(() async {
    if (owner <= 0) throw StateError('A signed-in account is required.');
    if (_owner == owner && _box?.isOpen == true) {
      return SecureRegisterStore._(owner);
    }
    final recordedOwner = await _secure.read(key: _ownerName);
    if (recordedOwner != '$owner') await _erase();
    var encoded = await _secure.read(key: _keyName);
    if (encoded == null) {
      encoded = base64UrlEncode(Hive.generateSecureKey());
      await _secure.write(key: _keyName, value: encoded);
      await _secure.write(key: _ownerName, value: '$owner');
    }
    _box = await Hive.openBox<dynamic>(
      _name,
      encryptionCipher: HiveAesCipher(base64Url.decode(encoded)),
    );
    _owner = owner;
    return SecureRegisterStore._(owner);
  });
  static Future<void> purge() => _locked(_erase);
  Box<dynamic> _checked() {
    if (_owner != owner || _box?.isOpen != true) {
      throw StateError('The register account changed.');
    }
    return _box!;
  }

  @override
  Future<Map<String, dynamic>?> read(String key) => _locked(() async {
    final value = _checked().get(key);
    if (value == null) return null;
    return Map<String, dynamic>.from(jsonDecode(jsonEncode(value)) as Map);
  });
  @override
  Future<void> write(String key, Map<String, dynamic> value) =>
      _locked(() async {
        await _checked().put(key, value);
      });
  @override
  Future<void> remove(String key) => _locked(() async {
    await _checked().delete(key);
  });
  @override
  Future<List<String>> keys() =>
      _locked(() async => _checked().keys.whereType<String>().toList());
}
