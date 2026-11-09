// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_memory.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorMemory _$TutorMemoryFromJson(Map<String, dynamic> json) => $checkedCreate(
  'TutorMemory',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'learner_id',
        'retention_days',
        'cleared_at',
        'note',
        'subjects',
        'contains_raw_chat',
      ],
    );
    final val = TutorMemory(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      retentionDays: $checkedConvert(
        'retention_days',
        (v) => (v as num).toInt(),
      ),
      clearedAt: $checkedConvert(
        'cleared_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      note: $checkedConvert('note', (v) => v as String),
      subjects: $checkedConvert(
        'subjects',
        (v) => (v as List<dynamic>)
            .map((e) => TutorMemorySubject.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      containsRawChat: $checkedConvert('contains_raw_chat', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'learnerId': 'learner_id',
    'retentionDays': 'retention_days',
    'clearedAt': 'cleared_at',
    'containsRawChat': 'contains_raw_chat',
  },
);

Map<String, dynamic> _$TutorMemoryToJson(TutorMemory instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'learner_id': instance.learnerId,
      'retention_days': instance.retentionDays,
      'cleared_at': instance.clearedAt?.toIso8601String(),
      'note': instance.note,
      'subjects': instance.subjects.map((e) => e.toJson()).toList(),
      'contains_raw_chat': instance.containsRawChat,
    };
