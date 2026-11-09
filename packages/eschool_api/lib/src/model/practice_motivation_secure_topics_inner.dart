//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_motivation_secure_topics_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeMotivationSecureTopicsInner {
  /// Returns a new [PracticeMotivationSecureTopicsInner] instance.
  PracticeMotivationSecureTopicsInner({
    required this.topicId,

    required this.name,

    required this.subject,

    required this.securedOn,
  });

  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'subject', required: true, includeIfNull: false)
  final String subject;

  @JsonKey(name: r'secured_on', required: true, includeIfNull: false)
  final DateTime securedOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeMotivationSecureTopicsInner &&
          other.topicId == topicId &&
          other.name == name &&
          other.subject == subject &&
          other.securedOn == securedOn;

  @override
  int get hashCode =>
      topicId.hashCode + name.hashCode + subject.hashCode + securedOn.hashCode;

  factory PracticeMotivationSecureTopicsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$PracticeMotivationSecureTopicsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$PracticeMotivationSecureTopicsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
