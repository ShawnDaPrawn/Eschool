// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentListResponse _$ContentListResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ContentListResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = ContentListResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => ContentList.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$ContentListResponseToJson(
  ContentListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
