//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/recognition_learner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'recognition_learner_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionLearnerPage {
  /// Returns a new [RecognitionLearnerPage] instance.
  RecognitionLearnerPage({required this.items, required this.nextAfter});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<RecognitionLearner> items;

  // minimum: 1
  @JsonKey(name: r'next_after', required: true, includeIfNull: true)
  final int? nextAfter;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionLearnerPage &&
          other.items == items &&
          other.nextAfter == nextAfter;

  @override
  int get hashCode =>
      items.hashCode + (nextAfter == null ? 0 : nextAfter.hashCode);

  factory RecognitionLearnerPage.fromJson(Map<String, dynamic> json) =>
      _$RecognitionLearnerPageFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionLearnerPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
