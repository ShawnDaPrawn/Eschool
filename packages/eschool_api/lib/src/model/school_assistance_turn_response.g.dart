// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_turn_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceTurnResponse _$SchoolAssistanceTurnResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceTurnResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SchoolAssistanceTurnResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SchoolAssistanceTurn.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SchoolAssistanceTurnResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceTurnResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SchoolAssistanceTurnResponseToJson(
  SchoolAssistanceTurnResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SchoolAssistanceTurnResponseCodeEnumEnumMap[instance.code]!,
};

const _$SchoolAssistanceTurnResponseCodeEnumEnumMap = {
  SchoolAssistanceTurnResponseCodeEnum.number200: 200,
  SchoolAssistanceTurnResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
