// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'enrolment_list_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EnrolmentListResponseData _$EnrolmentListResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('EnrolmentListResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items', 'pagination']);
  final val = EnrolmentListResponseData(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => Enrolment.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    pagination: $checkedConvert(
      'pagination',
      (v) => Pagination.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$EnrolmentListResponseDataToJson(
  EnrolmentListResponseData instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'pagination': instance.pagination.toJson(),
};
