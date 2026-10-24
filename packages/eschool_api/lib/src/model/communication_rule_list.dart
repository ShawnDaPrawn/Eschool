//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_role_rule.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_rule_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationRuleList {
  /// Returns a new [CommunicationRuleList] instance.
  CommunicationRuleList({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<CommunicationRoleRule> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationRuleList && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory CommunicationRuleList.fromJson(Map<String, dynamic> json) =>
      _$CommunicationRuleListFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationRuleListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
