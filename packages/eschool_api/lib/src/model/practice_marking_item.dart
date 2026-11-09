//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_marking_audio.dart';
import 'package:eschool_api/src/model/practice_marking_suggestion.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_marking_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeMarkingItem {
  /// Returns a new [PracticeMarkingItem] instance.
  PracticeMarkingItem({
    required this.id,

    required this.learnerId,

    required this.learnerName,

    required this.kind,

    required this.prompt,

    required this.answer,

    required this.accepted,

    required this.explanation,

    required this.answeredOn,

    required this.audio,

    required this.suggestion,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeMarkingItemKindEnum.unknownDefaultOpenApi,
  )
  final PracticeMarkingItemKindEnum kind;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(name: r'answer', required: true, includeIfNull: true)
  final String? answer;

  @JsonKey(name: r'accepted', required: true, includeIfNull: false)
  final List<String> accepted;

  @JsonKey(name: r'explanation', required: true, includeIfNull: false)
  final String explanation;

  @JsonKey(name: r'answered_on', required: true, includeIfNull: false)
  final String answeredOn;

  @JsonKey(name: r'audio', required: true, includeIfNull: true)
  final PracticeMarkingAudio? audio;

  /// Reserved for AI-assisted marking after a provider is qualified; always null today. A suggestion never marks an answer by itself.
  @JsonKey(name: r'suggestion', required: true, includeIfNull: true)
  final PracticeMarkingSuggestion? suggestion;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeMarkingItem &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.kind == kind &&
          other.prompt == prompt &&
          other.answer == answer &&
          other.accepted == accepted &&
          other.explanation == explanation &&
          other.answeredOn == answeredOn &&
          other.audio == audio &&
          other.suggestion == suggestion;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      kind.hashCode +
      prompt.hashCode +
      (answer == null ? 0 : answer.hashCode) +
      accepted.hashCode +
      explanation.hashCode +
      answeredOn.hashCode +
      (audio == null ? 0 : audio.hashCode) +
      (suggestion == null ? 0 : suggestion.hashCode);

  factory PracticeMarkingItem.fromJson(Map<String, dynamic> json) =>
      _$PracticeMarkingItemFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeMarkingItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeMarkingItemKindEnum {
  @JsonValue(r'short_text')
  shortText(r'short_text'),
  @JsonValue(r'audio')
  audio(r'audio'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeMarkingItemKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
