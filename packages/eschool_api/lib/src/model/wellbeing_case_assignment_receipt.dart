//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_assignment_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseAssignmentReceipt {
  /// Returns a new [WellbeingCaseAssignmentReceipt] instance.
  WellbeingCaseAssignmentReceipt({
    required this.id,

    required this.supportCaseId,

    required this.userId,

    required this.active,

    required this.revision,

    required this.startsOn,

    required this.endsOn,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'support_case_id', required: true, includeIfNull: false)
  final int supportCaseId;

  // minimum: 1
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseAssignmentReceipt &&
          other.id == id &&
          other.supportCaseId == supportCaseId &&
          other.userId == userId &&
          other.active == active &&
          other.revision == revision &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn;

  @override
  int get hashCode =>
      id.hashCode +
      supportCaseId.hashCode +
      userId.hashCode +
      active.hashCode +
      revision.hashCode +
      startsOn.hashCode +
      endsOn.hashCode;

  factory WellbeingCaseAssignmentReceipt.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseAssignmentReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseAssignmentReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
