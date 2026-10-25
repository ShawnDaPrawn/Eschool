// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_consultation_results_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetConsultationResultsResponse _$GetConsultationResultsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetConsultationResultsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetConsultationResultsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ConsultationResults.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetConsultationResultsResponseToJson(
  GetConsultationResultsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
