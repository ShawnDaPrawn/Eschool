//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'mastery_link.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryLink {
  /// Returns a new [MasteryLink] instance.
  MasteryLink({
    required this.topicId,

    required this.prerequisiteId,

    required this.source_,
  });

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  // minimum: 1
  @JsonKey(name: r'prerequisite_id', required: true, includeIfNull: false)
  final int prerequisiteId;

  @JsonKey(name: r'source', required: true, includeIfNull: false)
  final String source_;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryLink &&
          other.topicId == topicId &&
          other.prerequisiteId == prerequisiteId &&
          other.source_ == source_;

  @override
  int get hashCode =>
      topicId.hashCode + prerequisiteId.hashCode + source_.hashCode;

  factory MasteryLink.fromJson(Map<String, dynamic> json) =>
      _$MasteryLinkFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryLinkToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
