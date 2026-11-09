//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'update_my_family_summary_preferences_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class UpdateMyFamilySummaryPreferencesRequest {
  /// Returns a new [UpdateMyFamilySummaryPreferencesRequest] instance.
  UpdateMyFamilySummaryPreferencesRequest({
    required this.enabled,

    required this.sendAt,

    this.timezone,
  });

  @JsonKey(name: r'enabled', required: true, includeIfNull: false)
  final bool enabled;

  /// A quarter hour on your local day (the summary job runs every 15 minutes), outside your quiet hours as they fall in their own time zone.
  @JsonKey(name: r'send_at', required: true, includeIfNull: false)
  final String sendAt;

  /// An IANA time zone such as Africa/Johannesburg.
  @JsonKey(name: r'timezone', required: false, includeIfNull: false)
  final String? timezone;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is UpdateMyFamilySummaryPreferencesRequest &&
          other.enabled == enabled &&
          other.sendAt == sendAt &&
          other.timezone == timezone;

  @override
  int get hashCode =>
      enabled.hashCode +
      sendAt.hashCode +
      (timezone == null ? 0 : timezone.hashCode);

  factory UpdateMyFamilySummaryPreferencesRequest.fromJson(
    Map<String, dynamic> json,
  ) => _$UpdateMyFamilySummaryPreferencesRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$UpdateMyFamilySummaryPreferencesRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
