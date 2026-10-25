//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_team_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingTeamReceipt {
  /// Returns a new [WellbeingTeamReceipt] instance.
  WellbeingTeamReceipt({
    required this.id,

    required this.categoryId,

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
  @JsonKey(name: r'category_id', required: true, includeIfNull: false)
  final int categoryId;

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
      other is WellbeingTeamReceipt &&
          other.id == id &&
          other.categoryId == categoryId &&
          other.userId == userId &&
          other.active == active &&
          other.revision == revision &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn;

  @override
  int get hashCode =>
      id.hashCode +
      categoryId.hashCode +
      userId.hashCode +
      active.hashCode +
      revision.hashCode +
      startsOn.hashCode +
      endsOn.hashCode;

  factory WellbeingTeamReceipt.fromJson(Map<String, dynamic> json) =>
      _$WellbeingTeamReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingTeamReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
