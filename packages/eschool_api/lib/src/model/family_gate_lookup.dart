//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_gate_clearance.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_gate_lookup.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyGateLookup {
  /// Returns a new [FamilyGateLookup] instance.
  FamilyGateLookup({
    required this.available,

    required this.scanId,

    required this.learnerId,

    required this.learnerName,

    required this.passes,

    required this.collectorCheckRequired,
  });

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(name: r'scan_id', required: true, includeIfNull: false)
  final String scanId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(name: r'passes', required: true, includeIfNull: false)
  final List<FamilyGateClearance> passes;

  @JsonKey(
    name: r'collector_check_required',
    required: true,
    includeIfNull: false,
  )
  final bool collectorCheckRequired;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyGateLookup &&
          other.available == available &&
          other.scanId == scanId &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.passes == passes &&
          other.collectorCheckRequired == collectorCheckRequired;

  @override
  int get hashCode =>
      available.hashCode +
      scanId.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      passes.hashCode +
      collectorCheckRequired.hashCode;

  factory FamilyGateLookup.fromJson(Map<String, dynamic> json) =>
      _$FamilyGateLookupFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyGateLookupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
