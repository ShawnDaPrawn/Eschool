//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_rule_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationRuleWrite {
  /// Returns a new [CommunicationRuleWrite] instance.
  CommunicationRuleWrite({
    required this.operationId,

    required this.revision,

    required this.allowSchool,

    required this.requiresApproval,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'allow_school', required: true, includeIfNull: false)
  final bool allowSchool;

  @JsonKey(name: r'requires_approval', required: true, includeIfNull: false)
  final bool requiresApproval;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationRuleWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.allowSchool == allowSchool &&
          other.requiresApproval == requiresApproval;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      allowSchool.hashCode +
      requiresApproval.hashCode;

  factory CommunicationRuleWrite.fromJson(Map<String, dynamic> json) =>
      _$CommunicationRuleWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationRuleWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
