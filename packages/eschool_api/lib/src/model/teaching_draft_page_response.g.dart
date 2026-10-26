// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftPageResponse _$TeachingDraftPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingDraftPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TeachingDraftPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TeachingDraftPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$TeachingDraftPageResponseCodeEnumEnumMap,
        v,
        unknownValue: TeachingDraftPageResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$TeachingDraftPageResponseToJson(
  TeachingDraftPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$TeachingDraftPageResponseCodeEnumEnumMap[instance.code]!,
};

const _$TeachingDraftPageResponseCodeEnumEnumMap = {
  TeachingDraftPageResponseCodeEnum.number200: 200,
  TeachingDraftPageResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
