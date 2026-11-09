//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'giving_campaign_list_items_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingCampaignListItemsInner {
  /// Returns a new [GivingCampaignListItemsInner] instance.
  GivingCampaignListItemsInner({
    required this.id,

    required this.name,

    required this.description,

    required this.currency,

    required this.goalCents,

    required this.startsOn,

    required this.endsOn,

    required this.publicTotals,

    required this.state,

    required this.revision,

    required this.raisedCents,

    required this.pledgedCents,

    required this.donations,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'description', required: true, includeIfNull: true)
  final String? description;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @JsonKey(name: r'goal_cents', required: true, includeIfNull: true)
  final int? goalCents;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final DateTime startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final DateTime endsOn;

  @JsonKey(name: r'public_totals', required: true, includeIfNull: false)
  final bool publicTotals;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        GivingCampaignListItemsInnerStateEnum.unknownDefaultOpenApi,
  )
  final GivingCampaignListItemsInnerStateEnum state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'raised_cents', required: true, includeIfNull: false)
  final int raisedCents;

  @JsonKey(name: r'pledged_cents', required: true, includeIfNull: false)
  final int pledgedCents;

  @JsonKey(name: r'donations', required: true, includeIfNull: false)
  final int donations;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingCampaignListItemsInner &&
          other.id == id &&
          other.name == name &&
          other.description == description &&
          other.currency == currency &&
          other.goalCents == goalCents &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.publicTotals == publicTotals &&
          other.state == state &&
          other.revision == revision &&
          other.raisedCents == raisedCents &&
          other.pledgedCents == pledgedCents &&
          other.donations == donations;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (description == null ? 0 : description.hashCode) +
      currency.hashCode +
      (goalCents == null ? 0 : goalCents.hashCode) +
      startsOn.hashCode +
      endsOn.hashCode +
      publicTotals.hashCode +
      state.hashCode +
      revision.hashCode +
      raisedCents.hashCode +
      pledgedCents.hashCode +
      donations.hashCode;

  factory GivingCampaignListItemsInner.fromJson(Map<String, dynamic> json) =>
      _$GivingCampaignListItemsInnerFromJson(json);

  Map<String, dynamic> toJson() => _$GivingCampaignListItemsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GivingCampaignListItemsInnerStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const GivingCampaignListItemsInnerStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
