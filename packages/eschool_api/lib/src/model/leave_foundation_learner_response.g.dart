// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'leave_foundation_learner_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LeaveFoundationLearnerResponse _$LeaveFoundationLearnerResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LeaveFoundationLearnerResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = LeaveFoundationLearnerResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FoundationLeft.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$LeaveFoundationLearnerResponseToJson(
  LeaveFoundationLearnerResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
