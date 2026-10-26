//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_topic_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorTopicChoice {
  /// Returns a new [TutorTopicChoice] instance.
  TutorTopicChoice({required this.id, required this.name});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorTopicChoice && other.id == id && other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory TutorTopicChoice.fromJson(Map<String, dynamic> json) =>
      _$TutorTopicChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$TutorTopicChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
