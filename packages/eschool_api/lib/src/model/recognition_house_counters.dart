//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/recognition_house_counter.dart';
import 'package:json_annotation/json_annotation.dart';

part 'recognition_house_counters.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionHouseCounters {
  /// Returns a new [RecognitionHouseCounters] instance.
  RecognitionHouseCounters({
    required this.sessionYearId,

    required this.revision,

    required this.items,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<RecognitionHouseCounter> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionHouseCounters &&
          other.sessionYearId == sessionYearId &&
          other.revision == revision &&
          other.items == items;

  @override
  int get hashCode =>
      sessionYearId.hashCode + revision.hashCode + items.hashCode;

  factory RecognitionHouseCounters.fromJson(Map<String, dynamic> json) =>
      _$RecognitionHouseCountersFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionHouseCountersToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
