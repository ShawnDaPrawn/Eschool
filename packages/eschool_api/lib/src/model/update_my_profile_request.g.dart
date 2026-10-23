// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'update_my_profile_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UpdateMyProfileRequest _$UpdateMyProfileRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('UpdateMyProfileRequest', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['values']);
  final val = UpdateMyProfileRequest(
    profileType: $checkedConvert('profile_type', (v) => v as String?),
    values: $checkedConvert(
      'values',
      (v) => (v as Map<String, dynamic>).map(
        (k, e) => MapEntry(k, ProfileValue.fromJson(e as Map<String, dynamic>)),
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'profileType': 'profile_type'});

Map<String, dynamic> _$UpdateMyProfileRequestToJson(
  UpdateMyProfileRequest instance,
) => <String, dynamic>{
  'profile_type': ?instance.profileType,
  'values': instance.values.map((k, e) => MapEntry(k, e.toJson())),
};
