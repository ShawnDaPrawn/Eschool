import 'dart:convert';
import 'dart:io';

import 'package:collection/collection.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:hive/hive.dart';

/// Encrypts the entire existing auth box, including cached private profiles.
/// The key lives only in the OS keystore. Browser sessions use IndexedDB instead.
abstract final class NativeAuthBox {
  static const _keyName = 'navigateed_auth_box_key_v1';
  static const _complete = '_protected_auth_migration_v1';
  static const _secure = FlutterSecureStorage(
    iOptions: IOSOptions(
      accessibility: KeychainAccessibility.first_unlock_this_device,
    ),
    mOptions: MacOsOptions(
      accessibility: KeychainAccessibility.first_unlock_this_device,
    ),
  );

  /// Optional key callbacks let tests exercise actual disk migration and faults.
  static Future<Box<dynamic>> open({
    required String directory,
    required String name,
    List<String> otherLegacyDirectories = const [],
    Future<String?> Function()? readKey,
    Future<void> Function(String)? writeKey,
  }) async {
    final read = readKey ?? () => _secure.read(key: _keyName);
    final write =
        writeKey ?? (value) => _secure.write(key: _keyName, value: value);
    final protectedPath = '$directory/protected_auth_v1';
    final fileName = name.toLowerCase();
    final exists = await File('$protectedPath/$fileName.hive').exists();
    var encoded =
        await read(); // A locked/unavailable keystore must fail closed.
    if (encoded == null) {
      if (exists) {
        // Restored files without their device-bound key must require sign-in,
        // never resurrect an older plaintext credential or cached identity.
        await _erase(protectedPath, fileName);
        for (final path in {directory, ...otherLegacyDirectories}) {
          await _erase(path, fileName);
        }
      }
      encoded = base64UrlEncode(Hive.generateSecureKey());
      await write(encoded);
      if (await read() != encoded) {
        throw StateError('Protected session key was not persisted.');
      }
    }
    final key = base64Url.decode(encoded);
    if (key.length != 32) throw StateError('Invalid protected session key.');
    await Directory(protectedPath).create(recursive: true);
    final cipher = HiveAesCipher(key);
    var box = await Hive.openBox<dynamic>(
      name,
      path: protectedPath,
      encryptionCipher: cipher,
      crashRecovery: false,
    );
    try {
      if (box.get(_complete) != true &&
          await File('$directory/$fileName.hive').exists()) {
        // A prior interruption may have left an incomplete encrypted copy. The
        // original remains authoritative until the complete copy is verified.
        await box.close();
        final legacy = await Hive.openBox<dynamic>(name, path: directory);
        final Map<dynamic, dynamic> snapshot;
        try {
          snapshot = legacy.toMap();
        } finally {
          await legacy.close();
        }
        box = await Hive.openBox<dynamic>(
          name,
          path: protectedPath,
          encryptionCipher: cipher,
          crashRecovery: false,
        );
        await box.clear();
        await box.putAll(snapshot);
        await box.flush();
        await box.close();
        box = await Hive.openBox<dynamic>(
          name,
          path: protectedPath,
          encryptionCipher: cipher,
          crashRecovery: false,
        );
        if (!const DeepCollectionEquality().equals(snapshot, box.toMap())) {
          await box.close();
          throw StateError('Protected session migration did not verify.');
        }
      }
      await box.put(_complete, true);
      await box.flush();
      for (final path in {directory, ...otherLegacyDirectories}) {
        await _erase(path, fileName);
      }
      return box;
    } catch (_) {
      if (box.isOpen) await box.close();
      rethrow;
    }
  }

  static Future<void> _erase(String directory, String name) async {
    for (final extension in ['hive', 'hivec', 'lock']) {
      final file = File('$directory/$name.$extension');
      if (await file.exists()) await file.delete();
    }
  }
}
