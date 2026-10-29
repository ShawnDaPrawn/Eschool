import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/services.dart';

class VendorSunTap {
  const VendorSunTap({
    required this.cardId,
    required this.picc,
    required this.mac,
  });
  final String cardId, picc, mac;

  factory VendorSunTap.fromUri(String value, int school) {
    final uri = Uri.tryParse(value);
    final backend = Uri.parse(AppConfig.apiBaseUrl);
    if (value.length > 1024 ||
        uri == null ||
        uri.scheme != 'https' ||
        uri.host != backend.host ||
        uri.userInfo.isNotEmpty ||
        uri.port != backend.port ||
        !uri.path.endsWith('/card-tap') ||
        uri.hasQuery ||
        !uri.hasFragment) {
      throw const FormatException('Use this school’s encoded identity card.');
    }
    if (uri.fragment.split('&').length != 4) {
      throw const FormatException(
        'Use this school’s current encoded identity card.',
      );
    }
    final fields = Uri.splitQueryString(uri.fragment);
    if (fields.length != 4 ||
        fields['s'] != '$school' ||
        !RegExp(
          r'^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$',
        ).hasMatch(fields['c'] ?? '') ||
        !RegExp(r'^[a-fA-F0-9]{32}$').hasMatch(fields['e'] ?? '') ||
        !RegExp(r'^[a-fA-F0-9]{16}$').hasMatch(fields['m'] ?? '')) {
      throw const FormatException(
        'Use this school’s current encoded identity card.',
      );
    }
    return VendorSunTap(
      cardId: fields['c']!,
      picc: fields['e']!,
      mac: fields['m']!,
    );
  }
}

class VendorNfc {
  static const _channel = MethodChannel('navigateed/vendor-payment-nfc');
  Future<VendorSunTap?> read(int school) async {
    final value = await _channel.invokeMethod<String>('read');
    return value == null ? null : VendorSunTap.fromUri(value, school);
  }

  Future<void> cancel() => _channel.invokeMethod<void>('cancel');
}
