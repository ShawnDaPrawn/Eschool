//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reasoning_review_shared.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReasoningReviewShared {
  /// Returns a new [ReasoningReviewShared] instance.
  ReasoningReviewShared({
    required this.learnerId,

    required this.text,

    required this.sharedAt,
  });

  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @JsonKey(name: r'shared_at', required: true, includeIfNull: false)
  final DateTime sharedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReasoningReviewShared &&
          other.learnerId == learnerId &&
          other.text == text &&
          other.sharedAt == sharedAt;

  @override
  int get hashCode => learnerId.hashCode + text.hashCode + sharedAt.hashCode;

  factory ReasoningReviewShared.fromJson(Map<String, dynamic> json) =>
      _$ReasoningReviewSharedFromJson(json);

  Map<String, dynamic> toJson() => _$ReasoningReviewSharedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
