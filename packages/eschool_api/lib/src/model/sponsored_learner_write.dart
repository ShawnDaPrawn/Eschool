//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'sponsored_learner_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SponsoredLearnerWrite {
  /// Returns a new [SponsoredLearnerWrite] instance.
  SponsoredLearnerWrite({required this.learnerId});

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SponsoredLearnerWrite && other.learnerId == learnerId;

  @override
  int get hashCode => learnerId.hashCode;

  factory SponsoredLearnerWrite.fromJson(Map<String, dynamic> json) =>
      _$SponsoredLearnerWriteFromJson(json);

  Map<String, dynamic> toJson() => _$SponsoredLearnerWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
