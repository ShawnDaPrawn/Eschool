// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_settings_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskSettingsWrite _$RiskSettingsWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RiskSettingsWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'expected_revision',
            'academic_threshold_bp',
            'minimum_coverage_bp',
            'absence_threshold_bp',
            'minimum_registers',
          ],
        );
        final val = RiskSettingsWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          expectedRevision: $checkedConvert(
            'expected_revision',
            (v) => (v as num).toInt(),
          ),
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
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'expectedRevision': 'expected_revision',
        'academicThresholdBp': 'academic_threshold_bp',
        'minimumCoverageBp': 'minimum_coverage_bp',
        'absenceThresholdBp': 'absence_threshold_bp',
        'minimumRegisters': 'minimum_registers',
      },
    );

Map<String, dynamic> _$RiskSettingsWriteToJson(RiskSettingsWrite instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'expected_revision': instance.expectedRevision,
      'academic_threshold_bp': instance.academicThresholdBp,
      'minimum_coverage_bp': instance.minimumCoverageBp,
      'absence_threshold_bp': instance.absenceThresholdBp,
      'minimum_registers': instance.minimumRegisters,
    };
