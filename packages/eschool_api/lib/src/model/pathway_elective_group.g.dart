// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_elective_group.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayElectiveGroup _$PathwayElectiveGroupFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PathwayElectiveGroup', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['title', 'choose', 'subject_ids']);
  final val = PathwayElectiveGroup(
    title: $checkedConvert('title', (v) => v as String),
    choose: $checkedConvert('choose', (v) => (v as num).toInt()),
    subjectIds: $checkedConvert(
      'subject_ids',
      (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'subjectIds': 'subject_ids'});

Map<String, dynamic> _$PathwayElectiveGroupToJson(
  PathwayElectiveGroup instance,
) => <String, dynamic>{
  'title': instance.title,
  'choose': instance.choose,
  'subject_ids': instance.subjectIds,
};
