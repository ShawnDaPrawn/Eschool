// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'teaching_draft_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TeachingDraftConfiguration _$TeachingDraftConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TeachingDraftConfiguration', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'school_id',
      'reader_id',
      'today',
      'writable',
      'groups',
    ],
  );
  final val = TeachingDraftConfiguration(
    schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
    readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    today: $checkedConvert('today', (v) => DateTime.parse(v as String)),
    writable: $checkedConvert('writable', (v) => v as bool),
    groups: $checkedConvert(
      'groups',
      (v) => (v as List<dynamic>)
          .map((e) => TeachingGroupChoice.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'schoolId': 'school_id', 'readerId': 'reader_id'});

Map<String, dynamic> _$TeachingDraftConfigurationToJson(
  TeachingDraftConfiguration instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'today': instance.today.toIso8601String(),
  'writable': instance.writable,
  'groups': instance.groups.map((e) => e.toJson()).toList(),
};
