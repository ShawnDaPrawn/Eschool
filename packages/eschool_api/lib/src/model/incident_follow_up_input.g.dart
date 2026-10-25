// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_follow_up_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentFollowUpInput _$IncidentFollowUpInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('IncidentFollowUpInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['secret']);
  final val = IncidentFollowUpInput(
    secret: $checkedConvert('secret', (v) => v as String),
    before: $checkedConvert('before', (v) => (v as num?)?.toInt()),
  );
  return val;
});

Map<String, dynamic> _$IncidentFollowUpInputToJson(
  IncidentFollowUpInput instance,
) => <String, dynamic>{'secret': instance.secret, 'before': ?instance.before};
