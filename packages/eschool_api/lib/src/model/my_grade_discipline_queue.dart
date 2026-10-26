//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/my_grade_pending_record.dart';
import 'package:json_annotation/json_annotation.dart';

part 'my_grade_discipline_queue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MyGradeDisciplineQueue {
  /// Returns a new [MyGradeDisciplineQueue] instance.
  MyGradeDisciplineQueue({
    required this.available,

    required this.items,

    required this.page,

    required this.lastPage,

    required this.total,
  });

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<MyGradePendingRecord> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: true)
  final int? total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MyGradeDisciplineQueue &&
          other.available == available &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.total == total;

  @override
  int get hashCode =>
      available.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode +
      (total == null ? 0 : total.hashCode);

  factory MyGradeDisciplineQueue.fromJson(Map<String, dynamic> json) =>
      _$MyGradeDisciplineQueueFromJson(json);

  Map<String, dynamic> toJson() => _$MyGradeDisciplineQueueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
