//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/approval_learner.dart';
import 'package:eschool_api/src/model/guardian_consent.dart';
import 'package:json_annotation/json_annotation.dart';

part 'guardian_approval.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GuardianApproval {
  /// Returns a new [GuardianApproval] instance.
  GuardianApproval({
    required this.learner,

    required this.status,

    required this.requestedAt,

    required this.consent,
  });

  @JsonKey(name: r'learner', required: true, includeIfNull: false)
  final ApprovalLearner learner;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GuardianApprovalStatusEnum.unknownDefaultOpenApi,
  )
  final GuardianApprovalStatusEnum status;

  @JsonKey(name: r'requested_at', required: true, includeIfNull: true)
  final DateTime? requestedAt;

  @JsonKey(name: r'consent', required: true, includeIfNull: false)
  final GuardianConsent consent;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GuardianApproval &&
          other.learner == learner &&
          other.status == status &&
          other.requestedAt == requestedAt &&
          other.consent == consent;

  @override
  int get hashCode =>
      learner.hashCode +
      status.hashCode +
      (requestedAt == null ? 0 : requestedAt.hashCode) +
      consent.hashCode;

  factory GuardianApproval.fromJson(Map<String, dynamic> json) =>
      _$GuardianApprovalFromJson(json);

  Map<String, dynamic> toJson() => _$GuardianApprovalToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GuardianApprovalStatusEnum {
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'approved')
  approved(r'approved'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const GuardianApprovalStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
