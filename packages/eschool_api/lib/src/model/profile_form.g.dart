// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_form.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileForm _$ProfileFormFromJson(Map<String, dynamic> json) => $checkedCreate(
  'ProfileForm',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'person_id',
        'profile_type',
        'available_profiles',
        'categories',
      ],
    );
    final val = ProfileForm(
      personId: $checkedConvert('person_id', (v) => (v as num).toInt()),
      profileType: $checkedConvert('profile_type', (v) => v as String),
      availableProfiles: $checkedConvert(
        'available_profiles',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
      categories: $checkedConvert(
        'categories',
        (v) => (v as List<dynamic>)
            .map((e) => ProfileFormCategory.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'personId': 'person_id',
    'profileType': 'profile_type',
    'availableProfiles': 'available_profiles',
  },
);

Map<String, dynamic> _$ProfileFormToJson(ProfileForm instance) =>
    <String, dynamic>{
      'person_id': instance.personId,
      'profile_type': instance.profileType,
      'available_profiles': instance.availableProfiles,
      'categories': instance.categories.map((e) => e.toJson()).toList(),
    };
