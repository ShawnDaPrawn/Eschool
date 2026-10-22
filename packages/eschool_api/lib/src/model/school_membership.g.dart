// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_membership.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolMembership _$SchoolMembershipFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SchoolMembership', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['school', 'roles']);
      final val = SchoolMembership(
        school: $checkedConvert(
          'school',
          (v) => SchoolSummary.fromJson(v as Map<String, dynamic>),
        ),
        roles: $checkedConvert(
          'roles',
          (v) => (v as List<dynamic>).map((e) => e as String).toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$SchoolMembershipToJson(SchoolMembership instance) =>
    <String, dynamic>{
      'school': instance.school.toJson(),
      'roles': instance.roles,
    };
