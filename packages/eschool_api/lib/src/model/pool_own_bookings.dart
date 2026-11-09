//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pool_own_booking.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pool_own_bookings.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolOwnBookings {
  /// Returns a new [PoolOwnBookings] instance.
  PoolOwnBookings({required this.vetted, required this.items});

  @JsonKey(name: r'vetted', required: true, includeIfNull: false)
  final bool vetted;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<PoolOwnBooking> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolOwnBookings &&
          other.vetted == vetted &&
          other.items == items;

  @override
  int get hashCode => vetted.hashCode + items.hashCode;

  factory PoolOwnBookings.fromJson(Map<String, dynamic> json) =>
      _$PoolOwnBookingsFromJson(json);

  Map<String, dynamic> toJson() => _$PoolOwnBookingsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
