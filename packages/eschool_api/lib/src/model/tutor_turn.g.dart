// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_turn.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorTurn _$TutorTurnFromJson(Map<String, dynamic> json) => $checkedCreate(
  'TutorTurn',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'operation_id',
        'state',
        'question',
        'working',
        'response',
        'failure_code',
        'support_message',
        'provisional',
      ],
    );
    final val = TutorTurn(
      id: $checkedConvert('id', (v) => v as String),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$TutorTurnStateEnumEnumMap,
          v,
          unknownValue: TutorTurnStateEnum.unknownDefaultOpenApi,
        ),
      ),
      question: $checkedConvert('question', (v) => v as String?),
      working: $checkedConvert('working', (v) => v as String?),
      response: $checkedConvert(
        'response',
        (v) => v == null
            ? null
            : TutorFeedback.fromJson(v as Map<String, dynamic>),
      ),
      failureCode: $checkedConvert('failure_code', (v) => v as String?),
      supportMessage: $checkedConvert('support_message', (v) => v as String?),
      provisional: $checkedConvert('provisional', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'failureCode': 'failure_code',
    'supportMessage': 'support_message',
  },
);

Map<String, dynamic> _$TutorTurnToJson(TutorTurn instance) => <String, dynamic>{
  'id': instance.id,
  'operation_id': instance.operationId,
  'state': _$TutorTurnStateEnumEnumMap[instance.state]!,
  'question': instance.question,
  'working': instance.working,
  'response': instance.response?.toJson(),
  'failure_code': instance.failureCode,
  'support_message': instance.supportMessage,
  'provisional': instance.provisional,
};

const _$TutorTurnStateEnumEnumMap = {
  TutorTurnStateEnum.pending: 'pending',
  TutorTurnStateEnum.completed: 'completed',
  TutorTurnStateEnum.failed: 'failed',
  TutorTurnStateEnum.quarantined: 'quarantined',
  TutorTurnStateEnum.purged: 'purged',
  TutorTurnStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
