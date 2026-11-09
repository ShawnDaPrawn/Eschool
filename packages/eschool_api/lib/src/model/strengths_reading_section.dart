//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'strengths_reading_section.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsReadingSection {
  /// Returns a new [StrengthsReadingSection] instance.
  StrengthsReadingSection({
    required this.available,

    required this.title,

    required this.message,

    required this.items,
  });

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<Object> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsReadingSection &&
          other.available == available &&
          other.title == title &&
          other.message == message &&
          other.items == items;

  @override
  int get hashCode =>
      available.hashCode + title.hashCode + message.hashCode + items.hashCode;

  factory StrengthsReadingSection.fromJson(Map<String, dynamic> json) =>
      _$StrengthsReadingSectionFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsReadingSectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
