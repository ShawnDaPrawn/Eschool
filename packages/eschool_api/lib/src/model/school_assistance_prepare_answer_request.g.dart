// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_prepare_answer_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistancePrepareAnswerRequest
_$SchoolAssistancePrepareAnswerRequestFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SchoolAssistancePrepareAnswerRequest',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'documents',
            'include_calendar',
            'include_records',
            'question',
            'language',
          ],
        );
        final val = SchoolAssistancePrepareAnswerRequest(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          documents: $checkedConvert(
            'documents',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
          includeCalendar: $checkedConvert(
            'include_calendar',
            (v) => v as bool,
          ),
          includeRecords: $checkedConvert('include_records', (v) => v as bool),
          question: $checkedConvert('question', (v) => v as String),
          language: $checkedConvert('language', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'includeCalendar': 'include_calendar',
        'includeRecords': 'include_records',
      },
    );

Map<String, dynamic> _$SchoolAssistancePrepareAnswerRequestToJson(
  SchoolAssistancePrepareAnswerRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'documents': instance.documents,
  'include_calendar': instance.includeCalendar,
  'include_records': instance.includeRecords,
  'question': instance.question,
  'language': instance.language,
};
