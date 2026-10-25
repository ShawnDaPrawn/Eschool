// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_band_settings.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressBandSettings _$ProgressBandSettingsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ProgressBandSettings',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'high_bp',
        'secure_bp',
        'minimum_coverage_bp',
        'revision',
      ],
    );
    final val = ProgressBandSettings(
      highBp: $checkedConvert('high_bp', (v) => (v as num).toInt()),
      secureBp: $checkedConvert('secure_bp', (v) => (v as num).toInt()),
      minimumCoverageBp: $checkedConvert(
        'minimum_coverage_bp',
        (v) => (v as num).toInt(),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'highBp': 'high_bp',
    'secureBp': 'secure_bp',
    'minimumCoverageBp': 'minimum_coverage_bp',
  },
);

Map<String, dynamic> _$ProgressBandSettingsToJson(
  ProgressBandSettings instance,
) => <String, dynamic>{
  'high_bp': instance.highBp,
  'secure_bp': instance.secureBp,
  'minimum_coverage_bp': instance.minimumCoverageBp,
  'revision': instance.revision,
};
