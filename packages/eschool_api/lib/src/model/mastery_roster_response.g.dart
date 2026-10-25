// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_roster_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryRosterResponse _$MasteryRosterResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('MasteryRosterResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = MasteryRosterResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => MasteryRoster.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$MasteryRosterResponseToJson(
  MasteryRosterResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
