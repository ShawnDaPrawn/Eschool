// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_child.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestChild _$WeeklyDigestChildFromJson(Map<String, dynamic> json) =>
    $checkedCreate('WeeklyDigestChild', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = WeeklyDigestChild(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$WeeklyDigestChildToJson(WeeklyDigestChild instance) =>
    <String, dynamic>{'id': instance.id, 'name': instance.name};
