// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_transfer.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportTransfer _$LearnerPassportTransferFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearnerPassportTransfer',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'from_school_name',
        'to_school_name',
        'last_attended_on',
        'starts_on',
        'reports',
        'results',
      ],
    );
    final val = LearnerPassportTransfer(
      fromSchoolName: $checkedConvert('from_school_name', (v) => v as String),
      toSchoolName: $checkedConvert('to_school_name', (v) => v as String),
      lastAttendedOn: $checkedConvert(
        'last_attended_on',
        (v) => DateTime.parse(v as String),
      ),
      startsOn: $checkedConvert(
        'starts_on',
        (v) => DateTime.parse(v as String),
      ),
      reports: $checkedConvert(
        'reports',
        (v) => (v as List<dynamic>)
            .map(
              (e) => LearnerPassportTransferReportsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      results: $checkedConvert(
        'results',
        (v) => (v as List<dynamic>)
            .map(
              (e) => LearnerPassportTransferResultsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'fromSchoolName': 'from_school_name',
    'toSchoolName': 'to_school_name',
    'lastAttendedOn': 'last_attended_on',
    'startsOn': 'starts_on',
  },
);

Map<String, dynamic> _$LearnerPassportTransferToJson(
  LearnerPassportTransfer instance,
) => <String, dynamic>{
  'from_school_name': instance.fromSchoolName,
  'to_school_name': instance.toSchoolName,
  'last_attended_on': instance.lastAttendedOn.toIso8601String(),
  'starts_on': instance.startsOn.toIso8601String(),
  'reports': instance.reports.map((e) => e.toJson()).toList(),
  'results': instance.results.map((e) => e.toJson()).toList(),
};
