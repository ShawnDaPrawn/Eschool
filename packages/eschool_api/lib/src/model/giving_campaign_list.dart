//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/giving_campaign_list_items_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'giving_campaign_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingCampaignList {
  /// Returns a new [GivingCampaignList] instance.
  GivingCampaignList({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<GivingCampaignListItemsInner> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingCampaignList && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory GivingCampaignList.fromJson(Map<String, dynamic> json) =>
      _$GivingCampaignListFromJson(json);

  Map<String, dynamic> toJson() => _$GivingCampaignListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
