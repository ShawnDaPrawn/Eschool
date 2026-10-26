// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_photo_revocation_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingPhotoRevocationResponse _$TeachingPhotoRevocationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingPhotoRevocationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = TeachingPhotoRevocationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => TeachingPhotoRevocation.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$TeachingPhotoRevocationResponseCodeEnumEnumMap,
        v,
        unknownValue:
            TeachingPhotoRevocationResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$TeachingPhotoRevocationResponseToJson(
  TeachingPhotoRevocationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$TeachingPhotoRevocationResponseCodeEnumEnumMap[instance.code]!,
};

const _$TeachingPhotoRevocationResponseCodeEnumEnumMap = {
  TeachingPhotoRevocationResponseCodeEnum.number200: 200,
  TeachingPhotoRevocationResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
