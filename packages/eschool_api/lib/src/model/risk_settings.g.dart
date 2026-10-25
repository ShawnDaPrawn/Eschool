// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_settings.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskSettings _$RiskSettingsFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RiskSettings',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'academic_threshold_bp',
            'minimum_coverage_bp',
            'absence_threshold_bp',
            'minimum_registers',
            'revision',
            'rule_version',
          ],
        );
        final val = RiskSettings(
          academicThresholdBp: $checkedConvert(
            'academic_threshold_bp',
            (v) => (v as num).toInt(),
          ),
          minimumCoverageBp: $checkedConvert(
            'minimum_coverage_bp',
            (v) => (v as num).toInt(),
          ),
          absenceThresholdBp: $checkedConvert(
            'absence_threshold_bp',
            (v) => (v as num).toInt(),
          ),
          minimumRegisters: $checkedConvert(
            'minimum_registers',
            (v) => (v as num).toInt(),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          ruleVersion: $checkedConvert('rule_version', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'academicThresholdBp': 'academic_threshold_bp',
        'minimumCoverageBp': 'minimum_coverage_bp',
        'absenceThresholdBp': 'absence_threshold_bp',
        'minimumRegisters': 'minimum_registers',
        'ruleVersion': 'rule_version',
      },
    );

Map<String, dynamic> _$RiskSettingsToJson(RiskSettings instance) =>
    <String, dynamic>{
      'academic_threshold_bp': instance.academicThresholdBp,
      'minimum_coverage_bp': instance.minimumCoverageBp,
      'absence_threshold_bp': instance.absenceThresholdBp,
      'minimum_registers': instance.minimumRegisters,
      'revision': instance.revision,
      'rule_version': instance.ruleVersion,
    };
