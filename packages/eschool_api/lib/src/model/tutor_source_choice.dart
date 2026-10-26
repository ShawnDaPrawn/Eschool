//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_source_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorSourceChoice {
  /// Returns a new [TutorSourceChoice] instance.
  TutorSourceChoice({required this.id, required this.title});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorSourceChoice && other.id == id && other.title == title;

  @override
  int get hashCode => id.hashCode + title.hashCode;

  factory TutorSourceChoice.fromJson(Map<String, dynamic> json) =>
      _$TutorSourceChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$TutorSourceChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
