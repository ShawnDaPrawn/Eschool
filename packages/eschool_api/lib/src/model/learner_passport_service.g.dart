// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_service.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportService _$LearnerPassportServiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerPassportService', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['minutes', 'sessions']);
  final val = LearnerPassportService(
    minutes: $checkedConvert('minutes', (v) => (v as num).toInt()),
    sessions: $checkedConvert('sessions', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$LearnerPassportServiceToJson(
  LearnerPassportService instance,
) => <String, dynamic>{
  'minutes': instance.minutes,
  'sessions': instance.sessions,
};
