//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/recognition_point_entry.dart';
import 'package:json_annotation/json_annotation.dart';

part 'recognition_point_history.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionPointHistory {
  /// Returns a new [RecognitionPointHistory] instance.
  RecognitionPointHistory({required this.items, required this.nextBefore});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<RecognitionPointEntry> items;

  // minimum: 1
  @JsonKey(name: r'next_before', required: true, includeIfNull: true)
  final int? nextBefore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionPointHistory &&
          other.items == items &&
          other.nextBefore == nextBefore;

  @override
  int get hashCode =>
      items.hashCode + (nextBefore == null ? 0 : nextBefore.hashCode);

  factory RecognitionPointHistory.fromJson(Map<String, dynamic> json) =>
      _$RecognitionPointHistoryFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionPointHistoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
