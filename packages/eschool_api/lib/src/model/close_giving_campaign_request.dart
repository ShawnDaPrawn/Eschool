//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'close_giving_campaign_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CloseGivingCampaignRequest {
  /// Returns a new [CloseGivingCampaignRequest] instance.
  CloseGivingCampaignRequest({
    required this.operationId,

    required this.revision,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CloseGivingCampaignRequest &&
          other.operationId == operationId &&
          other.revision == revision;

  @override
  int get hashCode => operationId.hashCode + revision.hashCode;

  factory CloseGivingCampaignRequest.fromJson(Map<String, dynamic> json) =>
      _$CloseGivingCampaignRequestFromJson(json);

  Map<String, dynamic> toJson() => _$CloseGivingCampaignRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
