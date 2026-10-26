//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_checkpoint_option.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_checkpoint_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorCheckpointQuestion {
  /// Returns a new [TutorCheckpointQuestion] instance.
  TutorCheckpointQuestion({
    required this.kind,

    required this.prompt,

    required this.options,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TutorCheckpointQuestionKindEnum.unknownDefaultOpenApi,
  )
  final TutorCheckpointQuestionKindEnum kind;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<TutorCheckpointOption> options;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorCheckpointQuestion &&
          other.kind == kind &&
          other.prompt == prompt &&
          other.options == options;

  @override
  int get hashCode => kind.hashCode + prompt.hashCode + options.hashCode;

  factory TutorCheckpointQuestion.fromJson(Map<String, dynamic> json) =>
      _$TutorCheckpointQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$TutorCheckpointQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TutorCheckpointQuestionKindEnum {
  @JsonValue(r'choice')
  choice(r'choice'),
  @JsonValue(r'number')
  number(r'number'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorCheckpointQuestionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
