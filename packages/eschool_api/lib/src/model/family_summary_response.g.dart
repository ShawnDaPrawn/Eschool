// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_summary_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilySummaryResponse _$FamilySummaryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilySummaryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = FamilySummaryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => FamilySummaryResponseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilySummaryResponseToJson(
  FamilySummaryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
