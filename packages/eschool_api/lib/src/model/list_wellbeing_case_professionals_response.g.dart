// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_wellbeing_case_professionals_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListWellbeingCaseProfessionalsResponse
_$ListWellbeingCaseProfessionalsResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ListWellbeingCaseProfessionalsResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = ListWellbeingCaseProfessionalsResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => WellbeingCaseChoicePage.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$ListWellbeingCaseProfessionalsResponseToJson(
  ListWellbeingCaseProfessionalsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
