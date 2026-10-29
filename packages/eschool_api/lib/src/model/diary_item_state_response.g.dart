// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diary_item_state_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiaryItemStateResponse _$DiaryItemStateResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DiaryItemStateResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = DiaryItemStateResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => DiaryItemState.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$DiaryItemStateResponseToJson(
  DiaryItemStateResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
