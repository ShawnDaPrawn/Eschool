// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_register_sync_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRegisterSyncData _$AcademicRegisterSyncDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicRegisterSyncData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['register_id', 'revision', 'entries']);
  final val = AcademicRegisterSyncData(
    registerId: $checkedConvert('register_id', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    entries: $checkedConvert(
      'entries',
      (v) => (v as List<dynamic>)
          .map(
            (e) => AcademicRegisterEntryRevision.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'registerId': 'register_id'});

Map<String, dynamic> _$AcademicRegisterSyncDataToJson(
  AcademicRegisterSyncData instance,
) => <String, dynamic>{
  'register_id': instance.registerId,
  'revision': instance.revision,
  'entries': instance.entries.map((e) => e.toJson()).toList(),
};
