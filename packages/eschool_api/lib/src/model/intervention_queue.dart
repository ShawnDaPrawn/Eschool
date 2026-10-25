//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/intervention_queue_item.dart';
import 'package:json_annotation/json_annotation.dart';

part 'intervention_queue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class InterventionQueue {
  /// Returns a new [InterventionQueue] instance.
  InterventionQueue({
    required this.sessionYearId,

    required this.page,

    required this.lastPage,

    required this.interventions,
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

  @JsonKey(name: r'interventions', required: true, includeIfNull: false)
  final List<InterventionQueueItem> interventions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is InterventionQueue &&
          other.sessionYearId == sessionYearId &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.interventions == interventions;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      page.hashCode +
      lastPage.hashCode +
      interventions.hashCode;

  factory InterventionQueue.fromJson(Map<String, dynamic> json) =>
      _$InterventionQueueFromJson(json);

  Map<String, dynamic> toJson() => _$InterventionQueueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
