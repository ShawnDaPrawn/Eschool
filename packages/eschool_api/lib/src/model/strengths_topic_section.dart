//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_topic.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_topic_section.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsTopicSection {
  /// Returns a new [StrengthsTopicSection] instance.
  StrengthsTopicSection({
    required this.available,

    required this.title,

    required this.message,

    required this.items,
  });

  /// False when the source module is off at the school; the section is then not evidence of anything.
  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<StrengthsTopic> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsTopicSection &&
          other.available == available &&
          other.title == title &&
          other.message == message &&
          other.items == items;

  @override
  int get hashCode =>
      available.hashCode + title.hashCode + message.hashCode + items.hashCode;

  factory StrengthsTopicSection.fromJson(Map<String, dynamic> json) =>
      _$StrengthsTopicSectionFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsTopicSectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
