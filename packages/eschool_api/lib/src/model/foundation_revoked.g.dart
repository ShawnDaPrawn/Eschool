// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_revoked.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationRevoked _$FoundationRevokedFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FoundationRevoked', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['revoked']);
      final val = FoundationRevoked(
        revoked: $checkedConvert('revoked', (v) => v as bool),
      );
      return val;
    });

Map<String, dynamic> _$FoundationRevokedToJson(FoundationRevoked instance) =>
    <String, dynamic>{'revoked': instance.revoked};
