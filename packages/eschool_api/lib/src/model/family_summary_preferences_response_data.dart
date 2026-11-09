//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_summary_preferences_response_data_quiet_hours.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_summary_preferences_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilySummaryPreferencesResponseData {
  /// Returns a new [FamilySummaryPreferencesResponseData] instance.
  FamilySummaryPreferencesResponseData({
    required this.enabled,

    required this.sendAt,

    required this.timezone,

    required this.revision,

    required this.quietHours,
  });

  @JsonKey(name: r'enabled', required: true, includeIfNull: false)
  final bool enabled;

  @JsonKey(name: r'send_at', required: true, includeIfNull: false)
  final String sendAt;

  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'quiet_hours', required: true, includeIfNull: false)
  final FamilySummaryPreferencesResponseDataQuietHours quietHours;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilySummaryPreferencesResponseData &&
          other.enabled == enabled &&
          other.sendAt == sendAt &&
          other.timezone == timezone &&
          other.revision == revision &&
          other.quietHours == quietHours;

  @override
  int get hashCode =>
      enabled.hashCode +
      sendAt.hashCode +
      timezone.hashCode +
      revision.hashCode +
      quietHours.hashCode;

  factory FamilySummaryPreferencesResponseData.fromJson(
    Map<String, dynamic> json,
  ) => _$FamilySummaryPreferencesResponseDataFromJson(json);

  Map<String, dynamic> toJson() =>
      _$FamilySummaryPreferencesResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
