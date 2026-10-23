//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'profile_validation_rules.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProfileValidationRules {
  /// Returns a new [ProfileValidationRules] instance.
  ProfileValidationRules({this.min, this.max, this.email, this.past});

  @JsonKey(name: r'min', required: false, includeIfNull: false)
  final int? min;

  @JsonKey(name: r'max', required: false, includeIfNull: false)
  final int? max;

  @JsonKey(name: r'email', required: false, includeIfNull: false)
  final bool? email;

  @JsonKey(name: r'past', required: false, includeIfNull: false)
  final bool? past;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileValidationRules &&
          other.min == min &&
          other.max == max &&
          other.email == email &&
          other.past == past;

  @override
  int get hashCode =>
      min.hashCode + max.hashCode + email.hashCode + past.hashCode;

  factory ProfileValidationRules.fromJson(Map<String, dynamic> json) =>
      _$ProfileValidationRulesFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileValidationRulesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
