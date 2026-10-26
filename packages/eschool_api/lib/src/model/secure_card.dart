//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCard {
  /// Returns a new [SecureCard] instance.
  SecureCard({
    required this.id,

    required this.schoolId,

    required this.personId,

    required this.type,

    required this.status,

    required this.revision,

    required this.issuedAt,

    required this.frozenAt,

    required this.replacedBy,

    required this.hasChip,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'person_id', required: true, includeIfNull: false)
  final int personId;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'issued_at', required: true, includeIfNull: false)
  final DateTime issuedAt;

  @JsonKey(name: r'frozen_at', required: true, includeIfNull: true)
  final DateTime? frozenAt;

  @JsonKey(name: r'replaced_by', required: true, includeIfNull: true)
  final String? replacedBy;

  @JsonKey(name: r'has_chip', required: true, includeIfNull: false)
  final bool hasChip;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCard &&
          other.id == id &&
          other.schoolId == schoolId &&
          other.personId == personId &&
          other.type == type &&
          other.status == status &&
          other.revision == revision &&
          other.issuedAt == issuedAt &&
          other.frozenAt == frozenAt &&
          other.replacedBy == replacedBy &&
          other.hasChip == hasChip;

  @override
  int get hashCode =>
      id.hashCode +
      schoolId.hashCode +
      personId.hashCode +
      type.hashCode +
      status.hashCode +
      revision.hashCode +
      issuedAt.hashCode +
      (frozenAt == null ? 0 : frozenAt.hashCode) +
      (replacedBy == null ? 0 : replacedBy.hashCode) +
      hasChip.hashCode;

  factory SecureCard.fromJson(Map<String, dynamic> json) =>
      _$SecureCardFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
