//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_task_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingTaskChoice {
  /// Returns a new [TeachingTaskChoice] instance.
  TeachingTaskChoice({
    required this.id,

    required this.name,

    required this.termId,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: false)
  final int termId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingTaskChoice &&
          other.id == id &&
          other.name == name &&
          other.termId == termId;

  @override
  int get hashCode => id.hashCode + name.hashCode + termId.hashCode;

  factory TeachingTaskChoice.fromJson(Map<String, dynamic> json) =>
      _$TeachingTaskChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingTaskChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
