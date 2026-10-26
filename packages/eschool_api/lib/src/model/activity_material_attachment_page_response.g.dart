// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_material_attachment_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityMaterialAttachmentPageResponse
_$ActivityMaterialAttachmentPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityMaterialAttachmentPageResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ActivityMaterialAttachmentPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ActivityMaterialAttachmentPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$ActivityMaterialAttachmentPageResponseCodeEnumEnumMap,
        v,
        unknownValue: ActivityMaterialAttachmentPageResponseCodeEnum
            .unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$ActivityMaterialAttachmentPageResponseToJson(
  ActivityMaterialAttachmentPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code':
      _$ActivityMaterialAttachmentPageResponseCodeEnumEnumMap[instance.code]!,
};

const _$ActivityMaterialAttachmentPageResponseCodeEnumEnumMap = {
  ActivityMaterialAttachmentPageResponseCodeEnum.number200: 200,
  ActivityMaterialAttachmentPageResponseCodeEnum.unknownDefaultOpenApi:
      11184809,
};
