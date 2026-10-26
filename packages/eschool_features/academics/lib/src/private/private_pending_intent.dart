import 'dart:convert';
import 'dart:async';
import 'dart:typed_data';

import 'package:crypto/crypto.dart';
import 'package:hive/hive.dart';

/// A submitted exact request survives a short lifecycle interruption in memory.
/// No editor draft, original camera frame, key or ciphertext is persisted.
/// AES-CBC is authenticated with an independent encrypt-then-MAC key; the
/// source/account must be freshly checked by the reader before opening it.
class PrivatePendingIntent {
  PrivatePendingIntent({
    required this.school,
    required this.reader,
    required Map<String, dynamic> body,
    DateTime Function()? clock,
  }) : _clock = clock ?? DateTime.now {
    _expires = _clock().toUtc().add(const Duration(minutes: 15));
    final plain = Uint8List.fromList(utf8.encode(jsonEncode(body)));
    if (school < 1 || reader < 1 || plain.length > 2097152)
      throw const FormatException('The pending request is too large.');
    _key = Uint8List.fromList(Hive.generateSecureKey());
    _macKey = Uint8List.fromList(Hive.generateSecureKey());
    final cipher = HiveAesCipher(_key);
    final buffer = Uint8List(cipher.maxEncryptedSize(plain));
    final length = cipher.encrypt(plain, 0, plain.length, buffer, 0);
    _encrypted = Uint8List.fromList(buffer.sublist(0, length));
    _tag = Uint8List.fromList(Hmac(sha256, _macKey).convert(_encrypted).bytes);
    plain.fillRange(0, plain.length, 0);
    _timer = Timer(const Duration(minutes: 15), close);
  }
  final int school, reader;
  final DateTime Function() _clock;
  late final DateTime _expires;
  late Uint8List _key, _macKey, _encrypted, _tag;
  bool _closed = false;
  Timer? _timer;
  bool get expired => _closed || !_expires.isAfter(_clock().toUtc());
  Map<String, dynamic> open({
    required int school,
    required int reader,
    required bool authorized,
  }) {
    if (expired) {
      close();
      throw StateError(
        'The exact pending request expired. Check its operation status.',
      );
    }
    if (!authorized || school != this.school || reader != this.reader)
      throw StateError('The pending request scope changed.');
    final tag = Hmac(sha256, _macKey).convert(_encrypted).bytes;
    var difference = tag.length ^ _tag.length;
    for (var i = 0; i < tag.length; i++) {
      difference |= tag[i] ^ _tag[i];
    }
    if (difference != 0) {
      close();
      throw const FormatException('The pending request was damaged.');
    }
    final plain = Uint8List(_encrypted.length);
    try {
      final length = HiveAesCipher(
        _key,
      ).decrypt(_encrypted, 0, _encrypted.length, plain, 0);
      return Map<String, dynamic>.from(
        jsonDecode(utf8.decode(plain.sublist(0, length))) as Map,
      );
    } finally {
      plain.fillRange(0, plain.length, 0);
    }
  }

  void close() {
    _closed = true;
    _timer?.cancel();
    for (final bytes in [_key, _macKey, _encrypted, _tag]) {
      bytes.fillRange(0, bytes.length, 0);
    }
  }
}
