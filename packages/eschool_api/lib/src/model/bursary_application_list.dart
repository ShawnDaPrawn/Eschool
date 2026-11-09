//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/bursary_application_list_items_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'bursary_application_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryApplicationList {
  /// Returns a new [BursaryApplicationList] instance.
  BursaryApplicationList({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<BursaryApplicationListItemsInner> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryApplicationList && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory BursaryApplicationList.fromJson(Map<String, dynamic> json) =>
      _$BursaryApplicationListFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryApplicationListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
