// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_detail_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftDetailResponse _$TeachingDraftDetailResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingDraftDetailResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TeachingDraftDetailResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TeachingDraftDetail.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$TeachingDraftDetailResponseCodeEnumEnumMap,
        v,
        unknownValue: TeachingDraftDetailResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$TeachingDraftDetailResponseToJson(
  TeachingDraftDetailResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$TeachingDraftDetailResponseCodeEnumEnumMap[instance.code]!,
};

const _$TeachingDraftDetailResponseCodeEnumEnumMap = {
  TeachingDraftDetailResponseCodeEnum.number200: 200,
  TeachingDraftDetailResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
