// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_teacher_disclosure_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportTeacherDisclosureResponse _$SupportTeacherDisclosureResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SupportTeacherDisclosureResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = SupportTeacherDisclosureResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => SupportTeacherDisclosure.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$SupportTeacherDisclosureResponseToJson(
  SupportTeacherDisclosureResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
