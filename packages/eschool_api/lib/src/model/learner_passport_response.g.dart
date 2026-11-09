// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportResponse _$LearnerPassportResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerPassportResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = LearnerPassportResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => LearnerPassport.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$LearnerPassportResponseToJson(
  LearnerPassportResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
