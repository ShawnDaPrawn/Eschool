//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_bank_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeBankItem {
  /// Returns a new [PracticeBankItem] instance.
  PracticeBankItem({
    required this.id,

    required this.topicId,

    required this.revision,

    required this.active,

    required this.contentVersionId,

    required this.estimatedSeconds,

    required this.definition,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'content_version_id', required: true, includeIfNull: false)
  final int contentVersionId;

  // minimum: 60
  // maximum: 300
  @JsonKey(name: r'estimated_seconds', required: true, includeIfNull: false)
  final int estimatedSeconds;

  @JsonKey(name: r'definition', required: true, includeIfNull: false)
  final PracticeQuestion definition;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeBankItem &&
          other.id == id &&
          other.topicId == topicId &&
          other.revision == revision &&
          other.active == active &&
          other.contentVersionId == contentVersionId &&
          other.estimatedSeconds == estimatedSeconds &&
          other.definition == definition;

  @override
  int get hashCode =>
      id.hashCode +
      topicId.hashCode +
      revision.hashCode +
      active.hashCode +
      contentVersionId.hashCode +
      estimatedSeconds.hashCode +
      definition.hashCode;

  factory PracticeBankItem.fromJson(Map<String, dynamic> json) =>
      _$PracticeBankItemFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeBankItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
