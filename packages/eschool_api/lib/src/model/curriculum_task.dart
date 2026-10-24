//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/curriculum_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_task.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumTask {
  /// Returns a new [CurriculumTask] instance.
  CurriculumTask({
    required this.id,

    required this.sourceTaskId,

    required this.key,

    required this.name,

    required this.type,

    required this.component,

    required this.annualComponent,

    required this.weightBp,

    required this.annualWeightBp,

    required this.maxMark,

    required this.dueOn,

    required this.topicKeys,

    required this.questions,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'source_task_id', required: true, includeIfNull: true)
  final int? sourceTaskId;

  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'component', required: true, includeIfNull: false)
  final String component;

  @JsonKey(name: r'annual_component', required: true, includeIfNull: true)
  final String? annualComponent;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'weight_bp', required: true, includeIfNull: false)
  final int weightBp;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'annual_weight_bp', required: true, includeIfNull: false)
  final int annualWeightBp;

  @JsonKey(name: r'max_mark', required: true, includeIfNull: false)
  final String maxMark;

  @JsonKey(name: r'due_on', required: true, includeIfNull: true)
  final String? dueOn;

  @JsonKey(name: r'topic_keys', required: true, includeIfNull: false)
  final List<String> topicKeys;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<CurriculumQuestion> questions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumTask &&
          other.id == id &&
          other.sourceTaskId == sourceTaskId &&
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
      id.hashCode +
      (sourceTaskId == null ? 0 : sourceTaskId.hashCode) +
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

  factory CurriculumTask.fromJson(Map<String, dynamic> json) =>
      _$CurriculumTaskFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumTaskToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
