// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_results.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationResults _$ConsultationResultsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationResults', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'id',
      'closed',
      'anonymous',
      'response_count',
      'questions',
    ],
  );
  final val = ConsultationResults(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    closed: $checkedConvert('closed', (v) => v as bool),
    anonymous: $checkedConvert('anonymous', (v) => v as bool),
    responseCount: $checkedConvert('response_count', (v) => (v as num).toInt()),
    questions: $checkedConvert(
      'questions',
      (v) => (v as List<dynamic>)
          .map(
            (e) =>
                ConsultationResultQuestion.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'responseCount': 'response_count'});

Map<String, dynamic> _$ConsultationResultsToJson(
  ConsultationResults instance,
) => <String, dynamic>{
  'id': instance.id,
  'closed': instance.closed,
  'anonymous': instance.anonymous,
  'response_count': instance.responseCount,
  'questions': instance.questions.map((e) => e.toJson()).toList(),
};
