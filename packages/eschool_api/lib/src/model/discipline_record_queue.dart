//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_record_metadata.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_record_queue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineRecordQueue {
  /// Returns a new [DisciplineRecordQueue] instance.
  DisciplineRecordQueue({
    required this.sessionYearId,

    required this.page,

    required this.lastPage,

    required this.records,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'records', required: true, includeIfNull: false)
  final List<DisciplineRecordMetadata> records;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineRecordQueue &&
          other.sessionYearId == sessionYearId &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.records == records;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      page.hashCode +
      lastPage.hashCode +
      records.hashCode;

  factory DisciplineRecordQueue.fromJson(Map<String, dynamic> json) =>
      _$DisciplineRecordQueueFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineRecordQueueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
