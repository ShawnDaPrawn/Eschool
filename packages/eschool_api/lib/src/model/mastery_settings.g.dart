// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_settings.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasterySettings _$MasterySettingsFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'MasterySettings',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'family_marks_enabled',
            'revision',
            'policy_version',
          ],
        );
        final val = MasterySettings(
          familyMarksEnabled: $checkedConvert(
            'family_marks_enabled',
            (v) => v as bool,
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          policyVersion: $checkedConvert('policy_version', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'familyMarksEnabled': 'family_marks_enabled',
        'policyVersion': 'policy_version',
      },
    );

Map<String, dynamic> _$MasterySettingsToJson(MasterySettings instance) =>
    <String, dynamic>{
      'family_marks_enabled': instance.familyMarksEnabled,
      'revision': instance.revision,
      'policy_version': instance.policyVersion,
    };
