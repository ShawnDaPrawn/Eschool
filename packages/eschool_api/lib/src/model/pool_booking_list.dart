//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pool_booking_record.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pool_booking_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolBookingList {
  /// Returns a new [PoolBookingList] instance.
  PoolBookingList({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<PoolBookingRecord> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolBookingList && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory PoolBookingList.fromJson(Map<String, dynamic> json) =>
      _$PoolBookingListFromJson(json);

  Map<String, dynamic> toJson() => _$PoolBookingListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
