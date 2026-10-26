//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_visit_cancelled.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyVisitCancelled {
  /// Returns a new [FamilyVisitCancelled] instance.
  FamilyVisitCancelled({
    required this.id,

    required this.status,

    required this.revision,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyVisitCancelled &&
          other.id == id &&
          other.status == status &&
          other.revision == revision;

  @override
  int get hashCode => id.hashCode + status.hashCode + revision.hashCode;

  factory FamilyVisitCancelled.fromJson(Map<String, dynamic> json) =>
      _$FamilyVisitCancelledFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyVisitCancelledToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
