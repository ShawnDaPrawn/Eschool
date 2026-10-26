// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_turn.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceTurn _$SchoolAssistanceTurnFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistanceTurn',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'purpose',
        'state',
        'learner_id',
        'expires_at',
        'language',
        'review_text',
        'citations',
        'response',
        'school_id',
        'reader_id',
      ],
    );
    final val = SchoolAssistanceTurn(
      id: $checkedConvert('id', (v) => v as String),
      purpose: $checkedConvert(
        'purpose',
        (v) => $enumDecode(
          _$SchoolAssistanceTurnPurposeEnumEnumMap,
          v,
          unknownValue: SchoolAssistanceTurnPurposeEnum.unknownDefaultOpenApi,
        ),
      ),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$SchoolAssistanceTurnStateEnumEnumMap,
          v,
          unknownValue: SchoolAssistanceTurnStateEnum.unknownDefaultOpenApi,
        ),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
      language: $checkedConvert('language', (v) => v as String),
      reviewText: $checkedConvert('review_text', (v) => v as String),
      citations: $checkedConvert(
        'citations',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  SchoolAssistanceCitation.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      response: $checkedConvert(
        'response',
        (v) => v == null
            ? null
            : SchoolAssistanceAnswer.fromJson(v as Map<String, dynamic>),
      ),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'expiresAt': 'expires_at',
    'reviewText': 'review_text',
    'schoolId': 'school_id',
    'readerId': 'reader_id',
  },
);

Map<String, dynamic> _$SchoolAssistanceTurnToJson(
  SchoolAssistanceTurn instance,
) => <String, dynamic>{
  'id': instance.id,
  'purpose': _$SchoolAssistanceTurnPurposeEnumEnumMap[instance.purpose]!,
  'state': _$SchoolAssistanceTurnStateEnumEnumMap[instance.state]!,
  'learner_id': instance.learnerId,
  'expires_at': instance.expiresAt.toIso8601String(),
  'language': instance.language,
  'review_text': instance.reviewText,
  'citations': instance.citations.map((e) => e.toJson()).toList(),
  'response': instance.response?.toJson(),
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
};

const _$SchoolAssistanceTurnPurposeEnumEnumMap = {
  SchoolAssistanceTurnPurposeEnum.answer: 'answer',
  SchoolAssistanceTurnPurposeEnum.translation: 'translation',
  SchoolAssistanceTurnPurposeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$SchoolAssistanceTurnStateEnumEnumMap = {
  SchoolAssistanceTurnStateEnum.prepared: 'prepared',
  SchoolAssistanceTurnStateEnum.completed: 'completed',
  SchoolAssistanceTurnStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
