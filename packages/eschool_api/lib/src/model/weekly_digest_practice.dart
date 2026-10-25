//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_practice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestPractice {
  /// Returns a new [WeeklyDigestPractice] instance.
  WeeklyDigestPractice({required this.total, required this.completed});

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  // minimum: 0
  @JsonKey(name: r'completed', required: true, includeIfNull: false)
  final int completed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestPractice &&
          other.total == total &&
          other.completed == completed;

  @override
  int get hashCode => total.hashCode + completed.hashCode;

  factory WeeklyDigestPractice.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestPracticeFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestPracticeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
