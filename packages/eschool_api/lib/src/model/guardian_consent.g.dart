// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'guardian_consent.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GuardianConsent _$GuardianConsentFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GuardianConsent', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['version', 'text']);
      final val = GuardianConsent(
        version: $checkedConvert('version', (v) => v as String),
        text: $checkedConvert('text', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$GuardianConsentToJson(GuardianConsent instance) =>
    <String, dynamic>{'version': instance.version, 'text': instance.text};
