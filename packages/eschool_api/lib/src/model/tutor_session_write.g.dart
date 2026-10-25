// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_session_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorSessionWrite _$TutorSessionWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TutorSessionWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'group_id',
            'source_version_ids',
          ],
        );
        final val = TutorSessionWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
          sourceVersionIds: $checkedConvert(
            'source_version_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'groupId': 'group_id',
        'sourceVersionIds': 'source_version_ids',
      },
    );

Map<String, dynamic> _$TutorSessionWriteToJson(TutorSessionWrite instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'group_id': instance.groupId,
      'source_version_ids': instance.sourceVersionIds.toList(),
    };
