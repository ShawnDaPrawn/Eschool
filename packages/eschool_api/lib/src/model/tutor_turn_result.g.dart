// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_turn_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorTurnResult _$TutorTurnResultFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TutorTurnResult',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
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
        final val = TutorTurnResult(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          id: $checkedConvert('id', (v) => v as String),
          operationId: $checkedConvert('operation_id', (v) => v as String),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$TutorTurnResultStateEnumEnumMap,
              v,
              unknownValue: TutorTurnResultStateEnum.unknownDefaultOpenApi,
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
          supportMessage: $checkedConvert(
            'support_message',
            (v) => v as String?,
          ),
          provisional: $checkedConvert('provisional', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'operationId': 'operation_id',
        'failureCode': 'failure_code',
        'supportMessage': 'support_message',
      },
    );

Map<String, dynamic> _$TutorTurnResultToJson(TutorTurnResult instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'id': instance.id,
      'operation_id': instance.operationId,
      'state': _$TutorTurnResultStateEnumEnumMap[instance.state]!,
      'question': instance.question,
      'working': instance.working,
      'response': instance.response?.toJson(),
      'failure_code': instance.failureCode,
      'support_message': instance.supportMessage,
      'provisional': instance.provisional,
    };

const _$TutorTurnResultStateEnumEnumMap = {
  TutorTurnResultStateEnum.pending: 'pending',
  TutorTurnResultStateEnum.completed: 'completed',
  TutorTurnResultStateEnum.failed: 'failed',
  TutorTurnResultStateEnum.quarantined: 'quarantined',
  TutorTurnResultStateEnum.purged: 'purged',
  TutorTurnResultStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
