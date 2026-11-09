//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reenrolment_contract_list_items_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_contract_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentContractList {
  /// Returns a new [ReenrolmentContractList] instance.
  ReenrolmentContractList({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ReenrolmentContractListItemsInner> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentContractList && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory ReenrolmentContractList.fromJson(Map<String, dynamic> json) =>
      _$ReenrolmentContractListFromJson(json);

  Map<String, dynamic> toJson() => _$ReenrolmentContractListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
