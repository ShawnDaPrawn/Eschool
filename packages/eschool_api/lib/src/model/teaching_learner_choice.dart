//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_learner_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingLearnerChoice {
  /// Returns a new [TeachingLearnerChoice] instance.
  TeachingLearnerChoice({
    required this.id,

    required this.name,

    required this.approved,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'approved', required: true, includeIfNull: false)
  final bool approved;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingLearnerChoice &&
          other.id == id &&
          other.name == name &&
          other.approved == approved;

  @override
  int get hashCode => id.hashCode + name.hashCode + approved.hashCode;

  factory TeachingLearnerChoice.fromJson(Map<String, dynamic> json) =>
      _$TeachingLearnerChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingLearnerChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
