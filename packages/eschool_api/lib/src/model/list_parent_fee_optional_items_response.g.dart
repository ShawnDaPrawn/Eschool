// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_parent_fee_optional_items_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListParentFeeOptionalItemsResponse _$ListParentFeeOptionalItemsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ListParentFeeOptionalItemsResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ListParentFeeOptionalItemsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ParentFeeOptionalItemsPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ListParentFeeOptionalItemsResponseToJson(
  ListParentFeeOptionalItemsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
