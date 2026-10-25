// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_wellbeing_cases_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListWellbeingCasesResponse _$ListWellbeingCasesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ListWellbeingCasesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ListWellbeingCasesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingCasePage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ListWellbeingCasesResponseToJson(
  ListWellbeingCasesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
