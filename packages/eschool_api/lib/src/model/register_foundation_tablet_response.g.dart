// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'register_foundation_tablet_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RegisterFoundationTabletResponse _$RegisterFoundationTabletResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RegisterFoundationTabletResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = RegisterFoundationTabletResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FoundationRegistration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$RegisterFoundationTabletResponseToJson(
  RegisterFoundationTabletResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
