// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_record_queue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineRecordQueue _$DisciplineRecordQueueFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineRecordQueue',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['session_year_id', 'page', 'last_page', 'records'],
    );
    final val = DisciplineRecordQueue(
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
              (e) =>
                  DisciplineRecordMetadata.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'lastPage': 'last_page',
  },
);

Map<String, dynamic> _$DisciplineRecordQueueToJson(
  DisciplineRecordQueue instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'page': instance.page,
  'last_page': instance.lastPage,
  'records': instance.records.map((e) => e.toJson()).toList(),
};
