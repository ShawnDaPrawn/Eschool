// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_issue_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeIssueInput _$PracticeIssueInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PracticeIssueInput',
      json,
      ($checkedConvert) {
        $checkKeys(json, requiredKeys: const ['operation_id', 'practice_on']);
        final val = PracticeIssueInput(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          practiceOn: $checkedConvert('practice_on', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'practiceOn': 'practice_on',
      },
    );

Map<String, dynamic> _$PracticeIssueInputToJson(PracticeIssueInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'practice_on': instance.practiceOn,
    };
