//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/recognition_permanent_award.dart';
import 'package:json_annotation/json_annotation.dart';

part 'recognition_award_profile.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionAwardProfile {
  /// Returns a new [RecognitionAwardProfile] instance.
  RecognitionAwardProfile({required this.items, required this.nextBefore});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<RecognitionPermanentAward> items;

  // minimum: 1
  @JsonKey(name: r'next_before', required: true, includeIfNull: true)
  final int? nextBefore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionAwardProfile &&
          other.items == items &&
          other.nextBefore == nextBefore;

  @override
  int get hashCode =>
      items.hashCode + (nextBefore == null ? 0 : nextBefore.hashCode);

  factory RecognitionAwardProfile.fromJson(Map<String, dynamic> json) =>
      _$RecognitionAwardProfileFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionAwardProfileToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
