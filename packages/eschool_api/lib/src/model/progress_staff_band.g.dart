// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_staff_band.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressStaffBand _$ProgressStaffBandFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ProgressStaffBand',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'audience',
            'learner_id',
            'subject_id',
            'session_year_id',
            'term_id',
            'snapshot_id',
            'provisional',
            'band',
            'percentage_bp',
            'coverage_bp',
            'alerts_eligible',
            'settings_revision',
          ],
        );
        final val = ProgressStaffBand(
          audience: $checkedConvert(
            'audience',
            (v) => $enumDecode(
              _$ProgressStaffBandAudienceEnumEnumMap,
              v,
              unknownValue: ProgressStaffBandAudienceEnum.unknownDefaultOpenApi,
            ),
          ),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          termId: $checkedConvert('term_id', (v) => (v as num).toInt()),
          snapshotId: $checkedConvert('snapshot_id', (v) => (v as num).toInt()),
          provisional: $checkedConvert('provisional', (v) => v as bool),
          band: $checkedConvert(
            'band',
            (v) => $enumDecode(
              _$ProgressStaffBandBandEnumEnumMap,
              v,
              unknownValue: ProgressStaffBandBandEnum.unknownDefaultOpenApi,
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
        'learnerId': 'learner_id',
        'subjectId': 'subject_id',
        'sessionYearId': 'session_year_id',
        'termId': 'term_id',
        'snapshotId': 'snapshot_id',
        'percentageBp': 'percentage_bp',
        'coverageBp': 'coverage_bp',
        'alertsEligible': 'alerts_eligible',
        'settingsRevision': 'settings_revision',
      },
    );

Map<String, dynamic> _$ProgressStaffBandToJson(ProgressStaffBand instance) =>
    <String, dynamic>{
      'audience': _$ProgressStaffBandAudienceEnumEnumMap[instance.audience]!,
      'learner_id': instance.learnerId,
      'subject_id': instance.subjectId,
      'session_year_id': instance.sessionYearId,
      'term_id': instance.termId,
      'snapshot_id': instance.snapshotId,
      'provisional': instance.provisional,
      'band': _$ProgressStaffBandBandEnumEnumMap[instance.band]!,
      'percentage_bp': instance.percentageBp,
      'coverage_bp': instance.coverageBp,
      'alerts_eligible': instance.alertsEligible,
      'settings_revision': instance.settingsRevision,
    };

const _$ProgressStaffBandAudienceEnumEnumMap = {
  ProgressStaffBandAudienceEnum.staff: 'staff',
  ProgressStaffBandAudienceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ProgressStaffBandBandEnumEnumMap = {
  ProgressStaffBandBandEnum.high: 'high',
  ProgressStaffBandBandEnum.secure: 'secure',
  ProgressStaffBandBandEnum.support: 'support',
  ProgressStaffBandBandEnum.insufficientEvidence: 'insufficient_evidence',
  ProgressStaffBandBandEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
