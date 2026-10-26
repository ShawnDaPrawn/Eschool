// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_tablet.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationTablet _$FoundationTabletFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FoundationTablet', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'name', 'status', 'expires_at', 'active'],
      );
      final val = FoundationTablet(
        id: $checkedConvert('id', (v) => v as String),
        name: $checkedConvert('name', (v) => v as String),
        status: $checkedConvert('status', (v) => v as String),
        expiresAt: $checkedConvert(
          'expires_at',
          (v) => DateTime.parse(v as String),
        ),
        active: $checkedConvert('active', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'expiresAt': 'expires_at'});

Map<String, dynamic> _$FoundationTabletToJson(FoundationTablet instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'status': instance.status,
      'expires_at': instance.expiresAt.toIso8601String(),
      'active': instance.active,
    };
