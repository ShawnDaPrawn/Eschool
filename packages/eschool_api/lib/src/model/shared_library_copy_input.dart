//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_copy_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryCopyInput {
  /// Returns a new [SharedLibraryCopyInput] instance.
  SharedLibraryCopyInput({
    required this.operationId,

    required this.groupId,

    required this.topicId,

    required this.contentVersionId,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  // minimum: 1
  @JsonKey(name: r'content_version_id', required: true, includeIfNull: false)
  final int contentVersionId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryCopyInput &&
          other.operationId == operationId &&
          other.groupId == groupId &&
          other.topicId == topicId &&
          other.contentVersionId == contentVersionId;

  @override
  int get hashCode =>
      operationId.hashCode +
      groupId.hashCode +
      topicId.hashCode +
      contentVersionId.hashCode;

  factory SharedLibraryCopyInput.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryCopyInputFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryCopyInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
