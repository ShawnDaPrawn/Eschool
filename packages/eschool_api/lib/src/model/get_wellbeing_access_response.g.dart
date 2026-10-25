// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_wellbeing_access_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetWellbeingAccessResponse _$GetWellbeingAccessResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetWellbeingAccessResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetWellbeingAccessResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingAccess.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetWellbeingAccessResponseToJson(
  GetWellbeingAccessResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
