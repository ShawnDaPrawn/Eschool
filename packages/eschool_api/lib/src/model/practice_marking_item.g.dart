// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_marking_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeMarkingItem _$PracticeMarkingItemFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PracticeMarkingItem',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'learner_id',
            'learner_name',
            'kind',
            'prompt',
            'answer',
            'accepted',
            'explanation',
            'answered_on',
            'audio',
            'suggestion',
          ],
        );
        final val = PracticeMarkingItem(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          learnerName: $checkedConvert('learner_name', (v) => v as String),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$PracticeMarkingItemKindEnumEnumMap,
              v,
              unknownValue: PracticeMarkingItemKindEnum.unknownDefaultOpenApi,
            ),
          ),
          prompt: $checkedConvert('prompt', (v) => v as String),
          answer: $checkedConvert('answer', (v) => v as String?),
          accepted: $checkedConvert(
            'accepted',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
          explanation: $checkedConvert('explanation', (v) => v as String),
          answeredOn: $checkedConvert('answered_on', (v) => v as String),
          audio: $checkedConvert(
            'audio',
            (v) => v == null
                ? null
                : PracticeMarkingAudio.fromJson(v as Map<String, dynamic>),
          ),
          suggestion: $checkedConvert(
            'suggestion',
            (v) => v == null
                ? null
                : PracticeMarkingSuggestion.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'learnerName': 'learner_name',
        'answeredOn': 'answered_on',
      },
    );

Map<String, dynamic> _$PracticeMarkingItemToJson(
  PracticeMarkingItem instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'kind': _$PracticeMarkingItemKindEnumEnumMap[instance.kind]!,
  'prompt': instance.prompt,
  'answer': instance.answer,
  'accepted': instance.accepted,
  'explanation': instance.explanation,
  'answered_on': instance.answeredOn,
  'audio': instance.audio?.toJson(),
  'suggestion': instance.suggestion?.toJson(),
};

const _$PracticeMarkingItemKindEnumEnumMap = {
  PracticeMarkingItemKindEnum.shortText: 'short_text',
  PracticeMarkingItemKindEnum.audio: 'audio',
  PracticeMarkingItemKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
