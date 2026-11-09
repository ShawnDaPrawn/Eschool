//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'giving_campaign_change.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingCampaignChange {
  /// Returns a new [GivingCampaignChange] instance.
  GivingCampaignChange({
    required this.id,

    required this.state,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingCampaignChange &&
          other.id == id &&
          other.state == state &&
          other.revision == revision;

  @override
  int get hashCode => id.hashCode + state.hashCode + revision.hashCode;

  factory GivingCampaignChange.fromJson(Map<String, dynamic> json) =>
      _$GivingCampaignChangeFromJson(json);

  Map<String, dynamic> toJson() => _$GivingCampaignChangeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
