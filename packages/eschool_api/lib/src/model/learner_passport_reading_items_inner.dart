//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_reading_items_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportReadingItemsInner {
  /// Returns a new [LearnerPassportReadingItemsInner] instance.
  LearnerPassportReadingItemsInner({
    required this.title,

    this.author,

    this.finishedOn,
  });

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'author', required: false, includeIfNull: false)
  final String? author;

  @JsonKey(name: r'finished_on', required: false, includeIfNull: false)
  final DateTime? finishedOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportReadingItemsInner &&
          other.title == title &&
          other.author == author &&
          other.finishedOn == finishedOn;

  @override
  int get hashCode =>
      title.hashCode +
      (author == null ? 0 : author.hashCode) +
      (finishedOn == null ? 0 : finishedOn.hashCode);

  factory LearnerPassportReadingItemsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$LearnerPassportReadingItemsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$LearnerPassportReadingItemsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
