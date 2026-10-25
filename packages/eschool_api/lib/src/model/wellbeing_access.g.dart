// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_access.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingAccess _$WellbeingAccessFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'WellbeingAccess',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'can_configure',
            'professional',
            'can_triage',
            'can_casework',
            'can_write',
            'today',
            'school_slug',
            'emergency_guidance',
          ],
        );
        final val = WellbeingAccess(
          canConfigure: $checkedConvert('can_configure', (v) => v as bool),
          professional: $checkedConvert('professional', (v) => v as bool),
          canTriage: $checkedConvert('can_triage', (v) => v as bool),
          canCasework: $checkedConvert('can_casework', (v) => v as bool),
          canWrite: $checkedConvert('can_write', (v) => v as bool),
          today: $checkedConvert('today', (v) => v as String),
          schoolSlug: $checkedConvert('school_slug', (v) => v as String),
          emergencyGuidance: $checkedConvert(
            'emergency_guidance',
            (v) => v as String,
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'canConfigure': 'can_configure',
        'canTriage': 'can_triage',
        'canCasework': 'can_casework',
        'canWrite': 'can_write',
        'schoolSlug': 'school_slug',
        'emergencyGuidance': 'emergency_guidance',
      },
    );

Map<String, dynamic> _$WellbeingAccessToJson(WellbeingAccess instance) =>
    <String, dynamic>{
      'can_configure': instance.canConfigure,
      'professional': instance.professional,
      'can_triage': instance.canTriage,
      'can_casework': instance.canCasework,
      'can_write': instance.canWrite,
      'today': instance.today,
      'school_slug': instance.schoolSlug,
      'emergency_guidance': instance.emergencyGuidance,
    };
