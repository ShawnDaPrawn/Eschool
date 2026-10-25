// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_turn_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorTurnWrite _$TutorTurnWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TutorTurnWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'question']);
  final val = TutorTurnWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    question: $checkedConvert('question', (v) => v as String),
    working: $checkedConvert('working', (v) => v as String?),
    photoIds: $checkedConvert(
      'photo_ids',
      (v) => (v as List<dynamic>?)?.map((e) => e as String).toSet(),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id', 'photoIds': 'photo_ids'});

Map<String, dynamic> _$TutorTurnWriteToJson(TutorTurnWrite instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'question': instance.question,
      'working': ?instance.working,
      'photo_ids': ?instance.photoIds?.toList(),
    };
