// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_choices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftChoicesResponse _$TeachingDraftChoicesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingDraftChoicesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TeachingDraftChoicesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TeachingDraftChoices.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$TeachingDraftChoicesResponseCodeEnumEnumMap,
        v,
        unknownValue:
            TeachingDraftChoicesResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$TeachingDraftChoicesResponseToJson(
  TeachingDraftChoicesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$TeachingDraftChoicesResponseCodeEnumEnumMap[instance.code]!,
};

const _$TeachingDraftChoicesResponseCodeEnumEnumMap = {
  TeachingDraftChoicesResponseCodeEnum.number200: 200,
  TeachingDraftChoicesResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
