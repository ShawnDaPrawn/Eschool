//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'create_giving_campaign_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CreateGivingCampaignRequest {
  /// Returns a new [CreateGivingCampaignRequest] instance.
  CreateGivingCampaignRequest({
    required this.operationId,

    required this.name,

    this.description,

    required this.currency,

    this.goalCents,

    required this.startsOn,

    required this.endsOn,

    required this.publicTotals,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'description', required: false, includeIfNull: false)
  final String? description;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @JsonKey(name: r'goal_cents', required: false, includeIfNull: false)
  final int? goalCents;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final DateTime startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final DateTime endsOn;

  @JsonKey(name: r'public_totals', required: true, includeIfNull: false)
  final bool publicTotals;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CreateGivingCampaignRequest &&
          other.operationId == operationId &&
          other.name == name &&
          other.description == description &&
          other.currency == currency &&
          other.goalCents == goalCents &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.publicTotals == publicTotals;

  @override
  int get hashCode =>
      operationId.hashCode +
      name.hashCode +
      (description == null ? 0 : description.hashCode) +
      currency.hashCode +
      (goalCents == null ? 0 : goalCents.hashCode) +
      startsOn.hashCode +
      endsOn.hashCode +
      publicTotals.hashCode;

  factory CreateGivingCampaignRequest.fromJson(Map<String, dynamic> json) =>
      _$CreateGivingCampaignRequestFromJson(json);

  Map<String, dynamic> toJson() => _$CreateGivingCampaignRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
