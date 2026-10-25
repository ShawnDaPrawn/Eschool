// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_record_history.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineRecordHistory _$DisciplineRecordHistoryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineRecordHistory', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['record_id', 'versions']);
  final val = DisciplineRecordHistory(
    recordId: $checkedConvert('record_id', (v) => (v as num).toInt()),
    versions: $checkedConvert(
      'versions',
      (v) => (v as List<dynamic>)
          .map(
            (e) => DisciplineRecordVersion.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'recordId': 'record_id'});

Map<String, dynamic> _$DisciplineRecordHistoryToJson(
  DisciplineRecordHistory instance,
) => <String, dynamic>{
  'record_id': instance.recordId,
  'versions': instance.versions.map((e) => e.toJson()).toList(),
};
