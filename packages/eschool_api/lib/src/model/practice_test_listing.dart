//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_listing_item.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_listing.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestListing {
  /// Returns a new [PracticeTestListing] instance.
  PracticeTestListing({
    required this.learnerId,

    required this.serverTime,

    required this.tests,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'server_time', required: true, includeIfNull: false)
  final DateTime serverTime;

  @JsonKey(name: r'tests', required: true, includeIfNull: false)
  final List<PracticeTestListingItem> tests;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestListing &&
          other.learnerId == learnerId &&
          other.serverTime == serverTime &&
          other.tests == tests;

  @override
  int get hashCode => learnerId.hashCode + serverTime.hashCode + tests.hashCode;

  factory PracticeTestListing.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestListingFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestListingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
