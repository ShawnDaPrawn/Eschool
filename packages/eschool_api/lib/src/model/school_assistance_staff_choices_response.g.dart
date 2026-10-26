// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_staff_choices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceStaffChoicesResponse
_$SchoolAssistanceStaffChoicesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceStaffChoicesResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SchoolAssistanceStaffChoicesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SchoolAssistanceStaffChoices.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SchoolAssistanceStaffChoicesResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceStaffChoicesResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceStaffChoicesResponseToJson(
  SchoolAssistanceStaffChoicesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SchoolAssistanceStaffChoicesResponseCodeEnumEnumMap[instance.code]!,
};

const _$SchoolAssistanceStaffChoicesResponseCodeEnumEnumMap = {
  SchoolAssistanceStaffChoicesResponseCodeEnum.number200: 200,
  SchoolAssistanceStaffChoicesResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
