//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_service.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportService {
  /// Returns a new [LearnerPassportService] instance.
  LearnerPassportService({required this.minutes, required this.sessions});

  // minimum: 0
  @JsonKey(name: r'minutes', required: true, includeIfNull: false)
  final int minutes;

  // minimum: 0
  @JsonKey(name: r'sessions', required: true, includeIfNull: false)
  final int sessions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportService &&
          other.minutes == minutes &&
          other.sessions == sessions;

  @override
  int get hashCode => minutes.hashCode + sessions.hashCode;

  factory LearnerPassportService.fromJson(Map<String, dynamic> json) =>
      _$LearnerPassportServiceFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerPassportServiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
