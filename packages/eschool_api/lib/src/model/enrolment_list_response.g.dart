// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'enrolment_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EnrolmentListResponse _$EnrolmentListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('EnrolmentListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = EnrolmentListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => EnrolmentListResponseData.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$EnrolmentListResponseToJson(
  EnrolmentListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
