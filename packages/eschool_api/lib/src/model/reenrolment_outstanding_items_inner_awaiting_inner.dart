//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_outstanding_items_inner_awaiting_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentOutstandingItemsInnerAwaitingInner {
  /// Returns a new [ReenrolmentOutstandingItemsInnerAwaitingInner] instance.
  ReenrolmentOutstandingItemsInnerAwaitingInner({
    required this.userId,

    required this.name,
  });

  // minimum: 1
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentOutstandingItemsInnerAwaitingInner &&
          other.userId == userId &&
          other.name == name;

  @override
  int get hashCode => userId.hashCode + name.hashCode;

  factory ReenrolmentOutstandingItemsInnerAwaitingInner.fromJson(
    Map<String, dynamic> json,
  ) => _$ReenrolmentOutstandingItemsInnerAwaitingInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ReenrolmentOutstandingItemsInnerAwaitingInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
