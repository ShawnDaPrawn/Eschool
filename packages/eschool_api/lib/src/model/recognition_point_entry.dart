//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_point_entry.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionPointEntry {
  /// Returns a new [RecognitionPointEntry] instance.
  RecognitionPointEntry({
    required this.id,

    required this.houseId,

    required this.houseName,

    required this.categoryName,

    required this.points,

    required this.reason,

    required this.onDate,

    required this.public,

    required this.reversesId,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'house_id', required: true, includeIfNull: false)
  final int houseId;

  @JsonKey(name: r'house_name', required: true, includeIfNull: false)
  final String houseName;

  @JsonKey(name: r'category_name', required: true, includeIfNull: false)
  final String categoryName;

  @JsonKey(name: r'points', required: true, includeIfNull: false)
  final int points;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'public', required: true, includeIfNull: false)
  final bool public;

  // minimum: 1
  @JsonKey(name: r'reverses_id', required: true, includeIfNull: true)
  final int? reversesId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionPointEntry &&
          other.id == id &&
          other.houseId == houseId &&
          other.houseName == houseName &&
          other.categoryName == categoryName &&
          other.points == points &&
          other.reason == reason &&
          other.onDate == onDate &&
          other.public == public &&
          other.reversesId == reversesId;

  @override
  int get hashCode =>
      id.hashCode +
      houseId.hashCode +
      houseName.hashCode +
      categoryName.hashCode +
      points.hashCode +
      reason.hashCode +
      onDate.hashCode +
      public.hashCode +
      (reversesId == null ? 0 : reversesId.hashCode);

  factory RecognitionPointEntry.fromJson(Map<String, dynamic> json) =>
      _$RecognitionPointEntryFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionPointEntryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
