//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'competition_invite.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CompetitionInvite {
  /// Returns a new [CompetitionInvite] instance.
  CompetitionInvite({required this.operationId, required this.schoolId});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CompetitionInvite &&
          other.operationId == operationId &&
          other.schoolId == schoolId;

  @override
  int get hashCode => operationId.hashCode + schoolId.hashCode;

  factory CompetitionInvite.fromJson(Map<String, dynamic> json) =>
      _$CompetitionInviteFromJson(json);

  Map<String, dynamic> toJson() => _$CompetitionInviteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
