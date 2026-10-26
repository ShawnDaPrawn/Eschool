// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_foundation_learner_context_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetFoundationLearnerContextResponse
_$GetFoundationLearnerContextResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GetFoundationLearnerContextResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = GetFoundationLearnerContextResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => FoundationLearnerContext.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$GetFoundationLearnerContextResponseToJson(
  GetFoundationLearnerContextResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
