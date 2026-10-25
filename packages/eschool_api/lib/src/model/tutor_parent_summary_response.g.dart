// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_parent_summary_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorParentSummaryResponse _$TutorParentSummaryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorParentSummaryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TutorParentSummaryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TutorParentSummary.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$TutorParentSummaryResponseToJson(
  TutorParentSummaryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
