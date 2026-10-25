// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_band_settings_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressBandSettingsWrite _$ProgressBandSettingsWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ProgressBandSettingsWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'expected_revision',
        'high_bp',
        'secure_bp',
        'minimum_coverage_bp',
      ],
    );
    final val = ProgressBandSettingsWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      highBp: $checkedConvert('high_bp', (v) => (v as num).toInt()),
      secureBp: $checkedConvert('secure_bp', (v) => (v as num).toInt()),
      minimumCoverageBp: $checkedConvert(
        'minimum_coverage_bp',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
    'highBp': 'high_bp',
    'secureBp': 'secure_bp',
    'minimumCoverageBp': 'minimum_coverage_bp',
  },
);

Map<String, dynamic> _$ProgressBandSettingsWriteToJson(
  ProgressBandSettingsWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'high_bp': instance.highBp,
  'secure_bp': instance.secureBp,
  'minimum_coverage_bp': instance.minimumCoverageBp,
};
