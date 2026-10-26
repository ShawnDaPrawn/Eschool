// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_material_attachment_change_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityMaterialAttachmentChangeResponse
_$ActivityMaterialAttachmentChangeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityMaterialAttachmentChangeResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ActivityMaterialAttachmentChangeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) =>
          ActivityMaterialAttachmentChange.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$ActivityMaterialAttachmentChangeResponseCodeEnumEnumMap,
        v,
        unknownValue: ActivityMaterialAttachmentChangeResponseCodeEnum
            .unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$ActivityMaterialAttachmentChangeResponseToJson(
  ActivityMaterialAttachmentChangeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code':
      _$ActivityMaterialAttachmentChangeResponseCodeEnumEnumMap[instance.code]!,
};

const _$ActivityMaterialAttachmentChangeResponseCodeEnumEnumMap = {
  ActivityMaterialAttachmentChangeResponseCodeEnum.number200: 200,
  ActivityMaterialAttachmentChangeResponseCodeEnum.unknownDefaultOpenApi:
      11184809,
};
