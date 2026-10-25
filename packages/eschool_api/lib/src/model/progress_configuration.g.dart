// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressConfiguration _$ProgressConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ProgressConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_year_id',
        'can_manage_bands',
        'can_assign_counselling',
        'can_read_caseload',
        'settings',
        'restricted_staff',
        'years',
        'terms',
        'groups',
      ],
    );
    final val = ProgressConfiguration(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      canManageBands: $checkedConvert('can_manage_bands', (v) => v as bool),
      canAssignCounselling: $checkedConvert(
        'can_assign_counselling',
        (v) => v as bool,
      ),
      canReadCaseload: $checkedConvert('can_read_caseload', (v) => v as bool),
      settings: $checkedConvert(
        'settings',
        (v) => ProgressBandSettings.fromJson(v as Map<String, dynamic>),
      ),
      restrictedStaff: $checkedConvert(
        'restricted_staff',
        (v) => (v as List<dynamic>)
            .map((e) => ProgressOption.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      years: $checkedConvert(
        'years',
        (v) => (v as List<dynamic>)
            .map((e) => ProgressOption.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      terms: $checkedConvert(
        'terms',
        (v) => (v as List<dynamic>)
            .map((e) => ProgressOption.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      groups: $checkedConvert(
        'groups',
        (v) => (v as List<dynamic>)
            .map((e) => ProgressGroup.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'canManageBands': 'can_manage_bands',
    'canAssignCounselling': 'can_assign_counselling',
    'canReadCaseload': 'can_read_caseload',
    'restrictedStaff': 'restricted_staff',
  },
);

Map<String, dynamic> _$ProgressConfigurationToJson(
  ProgressConfiguration instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'can_manage_bands': instance.canManageBands,
  'can_assign_counselling': instance.canAssignCounselling,
  'can_read_caseload': instance.canReadCaseload,
  'settings': instance.settings.toJson(),
  'restricted_staff': instance.restrictedStaff.map((e) => e.toJson()).toList(),
  'years': instance.years.map((e) => e.toJson()).toList(),
  'terms': instance.terms.map((e) => e.toJson()).toList(),
  'groups': instance.groups.map((e) => e.toJson()).toList(),
};
