// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_family_history.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineFamilyHistory _$DisciplineFamilyHistoryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineFamilyHistory',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'session_year_id',
        'page',
        'last_page',
        'records',
      ],
    );
    final val = DisciplineFamilyHistory(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      records: $checkedConvert(
        'records',
        (v) => (v as List<dynamic>)
            .map(
              (e) => DisciplineFamilyRecord.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      years: $checkedConvert(
        'years',
        (v) => (v as List<dynamic>?)
            ?.map((e) => DisciplineChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'sessionYearId': 'session_year_id',
    'lastPage': 'last_page',
  },
);

Map<String, dynamic> _$DisciplineFamilyHistoryToJson(
  DisciplineFamilyHistory instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'session_year_id': instance.sessionYearId,
  'page': instance.page,
  'last_page': instance.lastPage,
  'records': instance.records.map((e) => e.toJson()).toList(),
  'years': ?instance.years?.map((e) => e.toJson()).toList(),
};
