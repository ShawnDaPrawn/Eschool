//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_marking_item.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_marking_queue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeMarkingQueue {
  /// Returns a new [PracticeMarkingQueue] instance.
  PracticeMarkingQueue({
    required this.groupId,

    required this.items,

    required this.page,

    required this.lastPage,

    required this.total,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<PracticeMarkingItem> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeMarkingQueue &&
          other.groupId == groupId &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.total == total;

  @override
  int get hashCode =>
      groupId.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode +
      total.hashCode;

  factory PracticeMarkingQueue.fromJson(Map<String, dynamic> json) =>
      _$PracticeMarkingQueueFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeMarkingQueueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
