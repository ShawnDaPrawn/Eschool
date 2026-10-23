// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'update_person_profile_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UpdatePersonProfileRequest _$UpdatePersonProfileRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('UpdatePersonProfileRequest', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['values']);
  final val = UpdatePersonProfileRequest(
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

Map<String, dynamic> _$UpdatePersonProfileRequestToJson(
  UpdatePersonProfileRequest instance,
) => <String, dynamic>{
  'profile_type': ?instance.profileType,
  'values': instance.values.map((k, e) => MapEntry(k, e.toJson())),
};
