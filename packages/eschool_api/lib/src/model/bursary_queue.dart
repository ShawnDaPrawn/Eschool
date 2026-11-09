//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/bursary_application_list_items_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'bursary_queue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryQueue {
  /// Returns a new [BursaryQueue] instance.
  BursaryQueue({required this.total, required this.page, required this.items});

  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<BursaryApplicationListItemsInner> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryQueue &&
          other.total == total &&
          other.page == page &&
          other.items == items;

  @override
  int get hashCode => total.hashCode + page.hashCode + items.hashCode;

  factory BursaryQueue.fromJson(Map<String, dynamic> json) =>
      _$BursaryQueueFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryQueueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
