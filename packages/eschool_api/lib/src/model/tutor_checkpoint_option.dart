//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_checkpoint_option.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorCheckpointOption {
  /// Returns a new [TutorCheckpointOption] instance.
  TutorCheckpointOption({required this.id, required this.text});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorCheckpointOption && other.id == id && other.text == text;

  @override
  int get hashCode => id.hashCode + text.hashCode;

  factory TutorCheckpointOption.fromJson(Map<String, dynamic> json) =>
      _$TutorCheckpointOptionFromJson(json);

  Map<String, dynamic> toJson() => _$TutorCheckpointOptionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
