// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'facilitator_profile.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FacilitatorProfile _$FacilitatorProfileFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FacilitatorProfile', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'user_id',
          'school_id',
          'name',
          'roles',
          'schools',
          'features',
        ],
      );
      final val = FacilitatorProfile(
        userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
        schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        roles: $checkedConvert(
          'roles',
          (v) => (v as List<dynamic>).map((e) => e as String).toList(),
        ),
        schools: $checkedConvert(
          'schools',
          (v) => (v as List<dynamic>)
              .map((e) => FacilitatorSchool.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        features: $checkedConvert(
          'features',
          (v) => FacilitatorFeatures.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    }, fieldKeyMap: const {'userId': 'user_id', 'schoolId': 'school_id'});

Map<String, dynamic> _$FacilitatorProfileToJson(FacilitatorProfile instance) =>
    <String, dynamic>{
      'user_id': instance.userId,
      'school_id': instance.schoolId,
      'name': instance.name,
      'roles': instance.roles,
      'schools': instance.schools.map((e) => e.toJson()).toList(),
      'features': instance.features.toJson(),
    };
