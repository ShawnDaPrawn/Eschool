// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'read_wellbeing_notice_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReadWellbeingNoticeResponse _$ReadWellbeingNoticeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReadWellbeingNoticeResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ReadWellbeingNoticeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingNoticeReadReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ReadWellbeingNoticeResponseToJson(
  ReadWellbeingNoticeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
