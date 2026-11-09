// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_start_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModeStartResponseData _$ChildModeStartResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ChildModeStartResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['token', 'session']);
  final val = ChildModeStartResponseData(
    token: $checkedConvert('token', (v) => v as String),
    session: $checkedConvert(
      'session',
      (v) => ChildModeSession.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$ChildModeStartResponseDataToJson(
  ChildModeStartResponseData instance,
) => <String, dynamic>{
  'token': instance.token,
  'session': instance.session.toJson(),
};
