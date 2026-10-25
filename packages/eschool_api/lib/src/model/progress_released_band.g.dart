// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_released_band.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressReleasedBand _$ProgressReleasedBandFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ProgressReleasedBand',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'subject_name',
        'grade_name',
        'release_id',
        'subject_id',
        'grade_level_id',
        'session_year_id',
        'term_id',
        'mode',
        'provisional',
        'rating_level',
        'released_at',
        'released_percentage_bp',
        'band',
        'percentage_bp',
        'coverage_bp',
        'alerts_eligible',
        'settings_revision',
      ],
    );
    final val = ProgressReleasedBand(
      subjectName: $checkedConvert('subject_name', (v) => v as String),
      gradeName: $checkedConvert('grade_name', (v) => v as String),
      releaseId: $checkedConvert('release_id', (v) => (v as num).toInt()),
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
      mode: $checkedConvert(
        'mode',
        (v) => $enumDecode(
          _$ProgressReleasedBandModeEnumEnumMap,
          v,
          unknownValue: ProgressReleasedBandModeEnum.unknownDefaultOpenApi,
        ),
      ),
      provisional: $checkedConvert('provisional', (v) => v as bool),
      ratingLevel: $checkedConvert('rating_level', (v) => (v as num?)?.toInt()),
      releasedAt: $checkedConvert(
        'released_at',
        (v) => DateTime.parse(v as String),
      ),
      releasedPercentageBp: $checkedConvert(
        'released_percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
      band: $checkedConvert(
        'band',
        (v) => $enumDecode(
          _$ProgressReleasedBandBandEnumEnumMap,
          v,
          unknownValue: ProgressReleasedBandBandEnum.unknownDefaultOpenApi,
        ),
      ),
      percentageBp: $checkedConvert(
        'percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
      coverageBp: $checkedConvert('coverage_bp', (v) => (v as num).toInt()),
      alertsEligible: $checkedConvert('alerts_eligible', (v) => v as bool),
      settingsRevision: $checkedConvert(
        'settings_revision',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectName': 'subject_name',
    'gradeName': 'grade_name',
    'releaseId': 'release_id',
    'subjectId': 'subject_id',
    'gradeLevelId': 'grade_level_id',
    'sessionYearId': 'session_year_id',
    'termId': 'term_id',
    'ratingLevel': 'rating_level',
    'releasedAt': 'released_at',
    'releasedPercentageBp': 'released_percentage_bp',
    'percentageBp': 'percentage_bp',
    'coverageBp': 'coverage_bp',
    'alertsEligible': 'alerts_eligible',
    'settingsRevision': 'settings_revision',
  },
);

Map<String, dynamic> _$ProgressReleasedBandToJson(
  ProgressReleasedBand instance,
) => <String, dynamic>{
  'subject_name': instance.subjectName,
  'grade_name': instance.gradeName,
  'release_id': instance.releaseId,
  'subject_id': instance.subjectId,
  'grade_level_id': instance.gradeLevelId,
  'session_year_id': instance.sessionYearId,
  'term_id': instance.termId,
  'mode': _$ProgressReleasedBandModeEnumEnumMap[instance.mode]!,
  'provisional': instance.provisional,
  'rating_level': instance.ratingLevel,
  'released_at': instance.releasedAt.toIso8601String(),
  'released_percentage_bp': instance.releasedPercentageBp,
  'band': _$ProgressReleasedBandBandEnumEnumMap[instance.band]!,
  'percentage_bp': instance.percentageBp,
  'coverage_bp': instance.coverageBp,
  'alerts_eligible': instance.alertsEligible,
  'settings_revision': instance.settingsRevision,
};

const _$ProgressReleasedBandModeEnumEnumMap = {
  ProgressReleasedBandModeEnum.running: 'running',
  ProgressReleasedBandModeEnum.official: 'official',
  ProgressReleasedBandModeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ProgressReleasedBandBandEnumEnumMap = {
  ProgressReleasedBandBandEnum.high: 'high',
  ProgressReleasedBandBandEnum.secure: 'secure',
  ProgressReleasedBandBandEnum.support: 'support',
  ProgressReleasedBandBandEnum.insufficientEvidence: 'insufficient_evidence',
  ProgressReleasedBandBandEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
