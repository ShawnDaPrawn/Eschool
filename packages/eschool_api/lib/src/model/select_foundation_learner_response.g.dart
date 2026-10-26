// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'select_foundation_learner_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SelectFoundationLearnerResponse _$SelectFoundationLearnerResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SelectFoundationLearnerResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SelectFoundationLearnerResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FoundationSelection.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SelectFoundationLearnerResponseToJson(
  SelectFoundationLearnerResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
