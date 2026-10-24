//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_audience.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_audience_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationAudienceList {
  /// Returns a new [CommunicationAudienceList] instance.
  CommunicationAudienceList({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<CommunicationAudience> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationAudienceList && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory CommunicationAudienceList.fromJson(Map<String, dynamic> json) =>
      _$CommunicationAudienceListFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationAudienceListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
