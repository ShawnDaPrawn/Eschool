//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/curriculum_question_write.dart';
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_task_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumTaskWrite {
  /// Returns a new [CurriculumTaskWrite] instance.
  CurriculumTaskWrite({
    this.key,

    required this.name,

    required this.type,

    required this.component,

    this.annualComponent,

    required this.weightBp,

    this.annualWeightBp,

    required this.maxMark,

    this.dueOn,

    required this.topicKeys,

    required this.questions,
  });

  @JsonKey(name: r'key', required: false, includeIfNull: false)
  final String? key;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'component', required: true, includeIfNull: false)
  final String component;

  @JsonKey(name: r'annual_component', required: false, includeIfNull: false)
  final String? annualComponent;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'weight_bp', required: true, includeIfNull: false)
  final int weightBp;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'annual_weight_bp', required: false, includeIfNull: false)
  final int? annualWeightBp;

  @JsonKey(name: r'max_mark', required: true, includeIfNull: false)
  final String maxMark;

  @JsonKey(name: r'due_on', required: false, includeIfNull: false)
  final String? dueOn;

  @JsonKey(name: r'topic_keys', required: true, includeIfNull: false)
  final List<String> topicKeys;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<CurriculumQuestionWrite> questions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumTaskWrite &&
          other.key == key &&
          other.name == name &&
          other.type == type &&
          other.component == component &&
          other.annualComponent == annualComponent &&
          other.weightBp == weightBp &&
          other.annualWeightBp == annualWeightBp &&
          other.maxMark == maxMark &&
          other.dueOn == dueOn &&
          other.topicKeys == topicKeys &&
          other.questions == questions;

  @override
  int get hashCode =>
      key.hashCode +
      name.hashCode +
      type.hashCode +
      component.hashCode +
      (annualComponent == null ? 0 : annualComponent.hashCode) +
      weightBp.hashCode +
      annualWeightBp.hashCode +
      maxMark.hashCode +
      (dueOn == null ? 0 : dueOn.hashCode) +
      topicKeys.hashCode +
      questions.hashCode;

  factory CurriculumTaskWrite.fromJson(Map<String, dynamic> json) =>
      _$CurriculumTaskWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumTaskWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
