// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_item_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentItemResponse _$ContentItemResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ContentItemResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = ContentItemResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => ContentItem.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$ContentItemResponseToJson(
  ContentItemResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
