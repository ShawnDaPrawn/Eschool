//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_option.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestOption {
  /// Returns a new [PracticeTestOption] instance.
  PracticeTestOption({required this.id, required this.text});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestOption && other.id == id && other.text == text;

  @override
  int get hashCode => id.hashCode + text.hashCode;

  factory PracticeTestOption.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestOptionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestOptionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
