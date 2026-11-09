//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pool_member_page_response_code.dart';
import 'package:eschool_api/src/model/pool_booking_list.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pool_booking_list_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolBookingListResponse {
  /// Returns a new [PoolBookingListResponse] instance.
  PoolBookingListResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final PoolBookingList data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final PoolMemberPageResponseCode? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolBookingListResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode +
      message.hashCode +
      data.hashCode +
      (code == null ? 0 : code.hashCode);

  factory PoolBookingListResponse.fromJson(Map<String, dynamic> json) =>
      _$PoolBookingListResponseFromJson(json);

  Map<String, dynamic> toJson() => _$PoolBookingListResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
