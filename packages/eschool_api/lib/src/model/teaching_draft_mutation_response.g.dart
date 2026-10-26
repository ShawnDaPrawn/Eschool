// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_mutation_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftMutationResponse _$TeachingDraftMutationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingDraftMutationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TeachingDraftMutationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TeachingDraftMutation.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$TeachingDraftMutationResponseCodeEnumEnumMap,
        v,
        unknownValue:
            TeachingDraftMutationResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$TeachingDraftMutationResponseToJson(
  TeachingDraftMutationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$TeachingDraftMutationResponseCodeEnumEnumMap[instance.code]!,
};

const _$TeachingDraftMutationResponseCodeEnumEnumMap = {
  TeachingDraftMutationResponseCodeEnum.number200: 200,
  TeachingDraftMutationResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
