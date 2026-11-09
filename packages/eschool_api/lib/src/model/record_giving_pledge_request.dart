//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'record_giving_pledge_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecordGivingPledgeRequest {
  /// Returns a new [RecordGivingPledgeRequest] instance.
  RecordGivingPledgeRequest({
    required this.operationId,

    required this.donorName,

    this.donorEmail,

    required this.amountCents,

    required this.pledgedOn,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'donor_name', required: true, includeIfNull: false)
  final String donorName;

  @JsonKey(name: r'donor_email', required: false, includeIfNull: false)
  final String? donorEmail;

  // minimum: 1
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  @JsonKey(name: r'pledged_on', required: true, includeIfNull: false)
  final DateTime pledgedOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecordGivingPledgeRequest &&
          other.operationId == operationId &&
          other.donorName == donorName &&
          other.donorEmail == donorEmail &&
          other.amountCents == amountCents &&
          other.pledgedOn == pledgedOn;

  @override
  int get hashCode =>
      operationId.hashCode +
      donorName.hashCode +
      (donorEmail == null ? 0 : donorEmail.hashCode) +
      amountCents.hashCode +
      pledgedOn.hashCode;

  factory RecordGivingPledgeRequest.fromJson(Map<String, dynamic> json) =>
      _$RecordGivingPledgeRequestFromJson(json);

  Map<String, dynamic> toJson() => _$RecordGivingPledgeRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
