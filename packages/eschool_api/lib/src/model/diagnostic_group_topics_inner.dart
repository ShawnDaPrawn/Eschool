//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'diagnostic_group_topics_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiagnosticGroupTopicsInner {
  /// Returns a new [DiagnosticGroupTopicsInner] instance.
  DiagnosticGroupTopicsInner({
    required this.libraryTopicId,

    required this.name,

    required this.secure,

    required this.developing,

    required this.gap,

    required this.notReached,
  });

  // minimum: 1
  @JsonKey(name: r'library_topic_id', required: true, includeIfNull: false)
  final int libraryTopicId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 0
  @JsonKey(name: r'secure', required: true, includeIfNull: false)
  final int secure;

  // minimum: 0
  @JsonKey(name: r'developing', required: true, includeIfNull: false)
  final int developing;

  // minimum: 0
  @JsonKey(name: r'gap', required: true, includeIfNull: false)
  final int gap;

  // minimum: 0
  @JsonKey(name: r'not_reached', required: true, includeIfNull: false)
  final int notReached;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiagnosticGroupTopicsInner &&
          other.libraryTopicId == libraryTopicId &&
          other.name == name &&
          other.secure == secure &&
          other.developing == developing &&
          other.gap == gap &&
          other.notReached == notReached;

  @override
  int get hashCode =>
      libraryTopicId.hashCode +
      name.hashCode +
      secure.hashCode +
      developing.hashCode +
      gap.hashCode +
      notReached.hashCode;

  factory DiagnosticGroupTopicsInner.fromJson(Map<String, dynamic> json) =>
      _$DiagnosticGroupTopicsInnerFromJson(json);

  Map<String, dynamic> toJson() => _$DiagnosticGroupTopicsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
