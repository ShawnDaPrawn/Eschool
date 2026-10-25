// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_preference_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestPreferenceInput _$WeeklyDigestPreferenceInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestPreferenceInput', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'language', 'revision'],
  );
  final val = WeeklyDigestPreferenceInput(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    language: $checkedConvert('language', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$WeeklyDigestPreferenceInputToJson(
  WeeklyDigestPreferenceInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'language': instance.language,
  'revision': instance.revision,
};
