// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_access.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeAccess _$PracticeAccessFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeAccess',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'reader_id',
        'learner_id',
        'practice_on',
        'writable',
        'staff_groups',
        'learner_groups',
      ],
    );
    final val = PracticeAccess(
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      practiceOn: $checkedConvert('practice_on', (v) => v as String),
      writable: $checkedConvert('writable', (v) => v as bool),
      staffGroups: $checkedConvert(
        'staff_groups',
        (v) => (v as List<dynamic>)
            .map((e) => PracticeGroupChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      learnerGroups: $checkedConvert(
        'learner_groups',
        (v) => (v as List<dynamic>)
            .map((e) => PracticeGroupChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'readerId': 'reader_id',
    'learnerId': 'learner_id',
    'practiceOn': 'practice_on',
    'staffGroups': 'staff_groups',
    'learnerGroups': 'learner_groups',
  },
);

Map<String, dynamic> _$PracticeAccessToJson(PracticeAccess instance) =>
    <String, dynamic>{
      'reader_id': instance.readerId,
      'learner_id': instance.learnerId,
      'practice_on': instance.practiceOn,
      'writable': instance.writable,
      'staff_groups': instance.staffGroups.map((e) => e.toJson()).toList(),
      'learner_groups': instance.learnerGroups.map((e) => e.toJson()).toList(),
    };
