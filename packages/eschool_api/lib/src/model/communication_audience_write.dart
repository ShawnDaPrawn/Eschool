//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_audience_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationAudienceWrite {
  /// Returns a new [CommunicationAudienceWrite] instance.
  CommunicationAudienceWrite({
    required this.operationId,

    required this.revision,

    required this.sessionYearId,

    required this.name,

    required this.active,

    required this.userIds,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @JsonKey(name: r'user_ids', required: true, includeIfNull: false)
  final Set<int> userIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationAudienceWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.sessionYearId == sessionYearId &&
          other.name == name &&
          other.active == active &&
          other.userIds == userIds;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      sessionYearId.hashCode +
      name.hashCode +
      active.hashCode +
      userIds.hashCode;

  factory CommunicationAudienceWrite.fromJson(Map<String, dynamic> json) =>
      _$CommunicationAudienceWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationAudienceWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
