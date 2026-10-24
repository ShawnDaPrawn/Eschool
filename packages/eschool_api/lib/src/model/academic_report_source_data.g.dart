// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_source_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportSourceData _$AcademicReportSourceDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportSourceData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'can_prepare',
        'cards',
        'fingerprint',
        'grade_level_id',
      ],
    );
    final val = AcademicReportSourceData(
      canPrepare: $checkedConvert('can_prepare', (v) => v as bool),
      cards: $checkedConvert(
        'cards',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicReportPayload.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'canPrepare': 'can_prepare',
    'gradeLevelId': 'grade_level_id',
  },
);

Map<String, dynamic> _$AcademicReportSourceDataToJson(
  AcademicReportSourceData instance,
) => <String, dynamic>{
  'can_prepare': instance.canPrepare,
  'cards': instance.cards.map((e) => e.toJson()).toList(),
  'fingerprint': instance.fingerprint,
  'grade_level_id': instance.gradeLevelId,
};
