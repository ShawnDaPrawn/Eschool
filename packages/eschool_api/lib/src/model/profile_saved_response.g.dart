// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_saved_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileSavedResponse _$ProfileSavedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProfileSavedResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ProfileSavedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) =>
          (v as Map<String, dynamic>?)?.map((k, e) => MapEntry(k, e as Object)),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ProfileSavedResponseToJson(
  ProfileSavedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data,
  'code': instance.code,
};
