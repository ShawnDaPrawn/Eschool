//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/badge_credential_list_response_data_items_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'badge_credential_list_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BadgeCredentialListResponseData {
  /// Returns a new [BadgeCredentialListResponseData] instance.
  BadgeCredentialListResponseData({
    required this.available,

    required this.items,
  });

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<BadgeCredentialListResponseDataItemsInner> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BadgeCredentialListResponseData &&
          other.available == available &&
          other.items == items;

  @override
  int get hashCode => available.hashCode + items.hashCode;

  factory BadgeCredentialListResponseData.fromJson(Map<String, dynamic> json) =>
      _$BadgeCredentialListResponseDataFromJson(json);

  Map<String, dynamic> toJson() =>
      _$BadgeCredentialListResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
