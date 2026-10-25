// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'list_wellbeing_notices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ListWellbeingNoticesResponse _$ListWellbeingNoticesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ListWellbeingNoticesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ListWellbeingNoticesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingNoticePage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ListWellbeingNoticesResponseToJson(
  ListWellbeingNoticesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
