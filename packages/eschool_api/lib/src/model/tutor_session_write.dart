//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_session_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorSessionWrite {
  /// Returns a new [TutorSessionWrite] instance.
  TutorSessionWrite({
    required this.operationId,

    required this.groupId,

    required this.sourceVersionIds,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'source_version_ids', required: true, includeIfNull: false)
  final Set<int> sourceVersionIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorSessionWrite &&
          other.operationId == operationId &&
          other.groupId == groupId &&
          other.sourceVersionIds == sourceVersionIds;

  @override
  int get hashCode =>
      operationId.hashCode + groupId.hashCode + sourceVersionIds.hashCode;

  factory TutorSessionWrite.fromJson(Map<String, dynamic> json) =>
      _$TutorSessionWriteFromJson(json);

  Map<String, dynamic> toJson() => _$TutorSessionWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
