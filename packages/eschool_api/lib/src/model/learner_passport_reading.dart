//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_passport_reading_items_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_reading.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportReading {
  /// Returns a new [LearnerPassportReading] instance.
  LearnerPassportReading({required this.available, required this.items});

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<LearnerPassportReadingItemsInner> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportReading &&
          other.available == available &&
          other.items == items;

  @override
  int get hashCode => available.hashCode + items.hashCode;

  factory LearnerPassportReading.fromJson(Map<String, dynamic> json) =>
      _$LearnerPassportReadingFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerPassportReadingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
