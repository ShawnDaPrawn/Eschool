//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_summary_preferences_response_data_quiet_hours.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilySummaryPreferencesResponseDataQuietHours {
  /// Returns a new [FamilySummaryPreferencesResponseDataQuietHours] instance.
  FamilySummaryPreferencesResponseDataQuietHours({
    required this.enabled,

    required this.startsAt,

    required this.endsAt,

    required this.timezone,
  });

  @JsonKey(name: r'enabled', required: true, includeIfNull: false)
  final bool enabled;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  /// The zone the quiet hours are kept in; the chosen time is checked against them there.
  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilySummaryPreferencesResponseDataQuietHours &&
          other.enabled == enabled &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.timezone == timezone;

  @override
  int get hashCode =>
      enabled.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      timezone.hashCode;

  factory FamilySummaryPreferencesResponseDataQuietHours.fromJson(
    Map<String, dynamic> json,
  ) => _$FamilySummaryPreferencesResponseDataQuietHoursFromJson(json);

  Map<String, dynamic> toJson() =>
      _$FamilySummaryPreferencesResponseDataQuietHoursToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
