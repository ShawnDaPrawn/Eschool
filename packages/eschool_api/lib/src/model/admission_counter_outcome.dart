//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/admission_counter_ticket.dart';
import 'package:json_annotation/json_annotation.dart';

part 'admission_counter_outcome.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionCounterOutcome {
  /// Returns a new [AdmissionCounterOutcome] instance.
  AdmissionCounterOutcome({
    required this.schoolId,

    required this.actorUserId,

    required this.vendorId,

    required this.receipt,

    required this.replayed,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

  @JsonKey(name: r'receipt', required: true, includeIfNull: false)
  final AdmissionCounterTicket receipt;

  @JsonKey(name: r'replayed', required: true, includeIfNull: false)
  final bool replayed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionCounterOutcome &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.vendorId == vendorId &&
          other.receipt == receipt &&
          other.replayed == replayed;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      vendorId.hashCode +
      receipt.hashCode +
      replayed.hashCode;

  factory AdmissionCounterOutcome.fromJson(Map<String, dynamic> json) =>
      _$AdmissionCounterOutcomeFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionCounterOutcomeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
